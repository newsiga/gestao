<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Executa de verdade a criação/vínculo no ASAAS — só é chamado depois
 * que o usuário confirmou na tela. Recebe as decisões sobre divergência
 * (por campo) e sobre assinatura existente (suspender ou excluir do
 * fechamento automático).
 */

require_once __DIR__ . '/asaas-client.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$contratoId = filter_input(INPUT_POST, 'contrato_id', FILTER_VALIDATE_INT);
$nome = trim($_POST['nome'] ?? '');
$cnpj = trim($_POST['cnpj'] ?? '');
$email = trim($_POST['email'] ?? '') ?: null;
$decisoesJson = $_POST['decisoes_divergencia'] ?? '{}'; // {"email": "manter_asaas" | "usar_local"}
$decisaoAssinatura = $_POST['decisao_assinatura'] ?? null; // "suspender" | "excluir_automatico" | null

$decisoesDivergencia = json_decode($decisoesJson, true) ?: [];

if (!$contratoId || $nome === '' || $cnpj === '') {
    http_response_code(422);
    echo json_encode(['erro' => 'Dados obrigatórios ausentes.']);
    exit;
}

try {
    $db = getDb();
    $asaas = new AsaasClient();

    $stmt = $db->prepare("
        SELECT c.*, cl.id AS cliente_id, cl.asaas_customer_id
        FROM contratos c JOIN clientes cl ON cl.id = c.cliente_id
        WHERE c.id = ?
    ");
    $stmt->execute([$contratoId]);
    $contrato = $stmt->fetch();

    if (!$contrato) {
        http_response_code(404);
        echo json_encode(['erro' => 'Contrato não encontrado.']);
        exit;
    }
    if ($contrato['status'] !== 'aprovado') {
        http_response_code(422);
        echo json_encode(['erro' => "Contrato não está em 'aprovado'."]);
        exit;
    }

    $db->beginTransaction();

    // ---------------------------------------------------------
    // 1. Cliente — reaproveita se já existe, resolve divergências
    //    campo a campo (nunca decide sozinho)
    // ---------------------------------------------------------
    $existente = $contrato['asaas_customer_id']
        ? ['id' => $contrato['asaas_customer_id']]
        : $asaas->buscarClientePorDocumento($cnpj);

    if ($existente && !$contrato['asaas_customer_id']) {
        // Recarrega o registro completo pra comparar campos
        $existenteCompleto = $asaas->buscarClientePorDocumento($cnpj);
        $camposParaAtualizar = [];

        foreach (['name' => $nome, 'email' => $email] as $campo => $valorLocal) {
            $valorAsaas = trim((string) ($existenteCompleto[$campo] ?? ''));
            if ($valorAsaas !== '' && $valorLocal && strcasecmp($valorAsaas, $valorLocal) !== 0) {
                $decisao = $decisoesDivergencia[$campo] ?? null;
                if ($decisao === null) {
                    throw new RuntimeException("Divergência não resolvida no campo '$campo'.");
                }
                if ($decisao === 'usar_local') {
                    $camposParaAtualizar[$campo] = $valorLocal;
                }
            }
        }
        if ($camposParaAtualizar) {
            $asaas->atualizarCliente($existente['id'], $camposParaAtualizar);
        }
        $clienteId = $existente['id'];
    } elseif ($existente) {
        $clienteId = $existente['id'];
    } else {
        $novo = $asaas->criarOuBuscarCliente($nome, $cnpj, $email);
        $clienteId = $novo['id'];
    }

    $db->prepare('UPDATE clientes SET asaas_customer_id = ?, cnpj = ?, email = ? WHERE id = ?')
       ->execute([$clienteId, $cnpj, $email, $contrato['cliente_id']]);

    // ---------------------------------------------------------
    // 2. Assinatura existente — só mexe se o usuário decidiu algo
    // ---------------------------------------------------------
    $faturamentoGerenciadoPor = 'sistema';
    $primeiraCompetenciaSistema = null;

    if ($decisaoAssinatura === 'suspender') {
        $assinaturas = $asaas->listarAssinaturasDoCliente($clienteId);
        foreach ($assinaturas as $assinatura) {
            if (($assinatura['status'] ?? '') === 'ACTIVE') {
                $asaas->suspenderAssinatura($assinatura['id']);
            }
        }
        // A assinatura antiga já cobriu tudo até o mês passado (a última
        // fatura dela, com vencimento no começo deste mês, é referente à
        // competência anterior) — o nosso fechamento só deve começar a
        // cobrar a PARTIR do mês corrente, nunca antes, senão duplica o
        // que a assinatura já cobrou.
        $primeiraCompetenciaSistema = date('Y-m');
    } elseif ($decisaoAssinatura === 'excluir_automatico') {
        $faturamentoGerenciadoPor = 'assinatura_asaas';
    }

    $db->prepare('UPDATE contratos SET faturamento_gerenciado_por = ?, primeira_competencia_sistema = ? WHERE id = ?')
       ->execute([$faturamentoGerenciadoPor, $primeiraCompetenciaSistema, $contratoId]);

    // ---------------------------------------------------------
    // 3. Cobranças — só parcelas ainda 'a_gerar' (idempotente)
    // ---------------------------------------------------------
    $cobrancasCriadas = 0;
    if ($contrato['tipo'] === 'projeto_parcelado') {
        $descricaoCobranca = $contrato['descricao_servico'] ?: null;
        $stmtParcelas = $db->prepare("SELECT id, numero, valor, vencimento FROM parcelas WHERE contrato_id = ? AND status = 'a_gerar' AND asaas_payment_id IS NULL");
        $stmtParcelas->execute([$contratoId]);
        foreach ($stmtParcelas->fetchAll() as $parcela) {
            $descricao = $descricaoCobranca ?: "Newsiga — parcela {$parcela['numero']} — contrato #$contratoId";
            $cobranca = $asaas->criarCobranca(
                $clienteId,
                (float) $parcela['valor'],
                $parcela['vencimento'],
                "parcela:{$parcela['id']}",
                $descricao
            );
            $db->prepare("UPDATE parcelas SET asaas_payment_id = ?, status = 'gerado' WHERE id = ?")
               ->execute([$cobranca['id'], $parcela['id']]);
            $cobrancasCriadas++;
        }
    }

    // ---------------------------------------------------------
    // 4. Contrato passa a 'ativo'
    // ---------------------------------------------------------
    $db->prepare("UPDATE contratos SET status = 'ativo' WHERE id = ?")->execute([$contratoId]);

    $db->commit();

    echo json_encode(['sucesso' => true, 'asaas_customer_id' => $clienteId, 'cobrancas_criadas' => $cobrancasCriadas]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao confirmar no ASAAS', 'detalhe' => $e->getMessage()]);
}
