<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Monta tudo que a tela de confirmação ASAAS precisa mostrar, a partir
 * de um contrato real: dados do cliente, se ele já existe no ASAAS,
 * divergências de cadastro, assinatura já existente, e as cobranças que
 * seriam criadas. NÃO cria nem altera nada no ASAAS — é só investigação.
 */

require_once __DIR__ . '/asaas-client.php';

header('Content-Type: application/json; charset=utf-8');

$contratoId = filter_input(INPUT_GET, 'contrato_id', FILTER_VALIDATE_INT);
if (!$contratoId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Contrato inválido.']);
    exit;
}

try {
    $db = getDb();

    $stmt = $db->prepare("
        SELECT c.*, cl.id AS cliente_id, cl.nome AS cliente_nome, cl.cnpj AS cliente_cnpj,
               cl.email AS cliente_email, cl.asaas_customer_id
        FROM contratos c
        JOIN clientes cl ON cl.id = c.cliente_id
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
        echo json_encode(['erro' => "Este contrato está como '{$contrato['status']}', não 'aprovado'."]);
        exit;
    }
    if (!$contrato['cliente_cnpj']) {
        http_response_code(422);
        echo json_encode(['erro' => 'Este cliente não tem CNPJ/CPF cadastrado — obrigatório pra criar no ASAAS. Edite o cliente antes de continuar.']);
        exit;
    }

    $asaas = new AsaasClient();

    $jaExiste = null;
    if (!$contrato['asaas_customer_id']) {
        $jaExiste = $asaas->buscarClientePorDocumento($contrato['cliente_cnpj']);
    }
    $customerIdConhecido = $contrato['asaas_customer_id'] ?: ($jaExiste['id'] ?? null);

    $divergencias = [];
    if ($jaExiste) {
        $mapaCampos = ['name' => ['rotulo' => 'Razão social', 'local' => $contrato['cliente_nome']], 'email' => ['rotulo' => 'E-mail', 'local' => $contrato['cliente_email']]];
        foreach ($mapaCampos as $campo => $info) {
            $valorAsaas = trim((string) ($jaExiste[$campo] ?? ''));
            $valorLocal = trim((string) ($info['local'] ?? ''));
            if ($valorAsaas !== '' && $valorLocal !== '' && strcasecmp($valorAsaas, $valorLocal) !== 0) {
                $divergencias[] = ['campo' => $campo, 'rotulo' => $info['rotulo'], 'no_asaas' => $valorAsaas, 'local' => $valorLocal];
            }
        }
    }

    $assinaturas = [];
    if ($customerIdConhecido) {
        $assinaturas = $asaas->listarAssinaturasDoCliente($customerIdConhecido);
    }

    $cobrancasPreview = [];
    if ($contrato['tipo'] === 'projeto_parcelado') {
        $stmtParcelas = $db->prepare("SELECT numero, valor, vencimento FROM parcelas WHERE contrato_id = ? AND status = 'a_gerar' ORDER BY numero");
        $stmtParcelas->execute([$contratoId]);
        $cobrancasPreview = $stmtParcelas->fetchAll();
    }

    echo json_encode([
        'sucesso' => true,
        'contrato' => $contrato,
        'cliente_ja_existe_no_asaas' => $customerIdConhecido !== null,
        'asaas_customer_id_existente' => $customerIdConhecido,
        'divergencias' => $divergencias,
        'assinaturas_existentes' => $assinaturas,
        'cobrancas_preview' => $cobrancasPreview,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao preparar confirmação', 'detalhe' => $e->getMessage()]);
}
