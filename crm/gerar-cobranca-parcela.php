<?php
/**
 * Script de EXECUÇÃO — cria a cobrança no ASAAS pra UMA parcela
 * específica (identificada pelo id da tabela `parcelas`), só se ela
 * estiver 'a_gerar' e sem asaas_payment_id. Existe pra resolver casos
 * pontuais (parcela que ficou travada, cobrança fantasma que precisou
 * ser resetada) sem precisar reaprovar o contrato inteiro de novo.
 *
 * Sempre mostra a URL base do ASAAS configurada ANTES de criar
 * qualquer coisa — confirma visualmente que é produção antes de seguir.
 *
 * Uso via navegador (modo simulação por padrão):
 *   https://crm.newsiga.com.br/gerar-cobranca-parcela.php?parcela_id=43&token=SEU_TOKEN
 * Pra criar de verdade:
 *   ...&confirmar=sim
 */

require_once __DIR__ . '/asaas-client.php';

header('Content-Type: text/plain; charset=utf-8');

$tokenRecebido = $_GET['token'] ?? '';
if (!defined('ASAAS_WEBHOOK_TOKEN') || !hash_equals(ASAAS_WEBHOOK_TOKEN, $tokenRecebido)) {
    http_response_code(401);
    echo "Token inválido.\n";
    exit;
}

$parcelaId = filter_input(INPUT_GET, 'parcela_id', FILTER_VALIDATE_INT);
if (!$parcelaId) {
    echo "Uso: ?parcela_id=ID&token=...\n";
    exit;
}

$modoReal = ($_GET['confirmar'] ?? '') === 'sim';

echo "Base URL do ASAAS configurada agora: " . (defined('ASAAS_BASE_URL') ? ASAAS_BASE_URL : '(não definida)') . "\n";
echo "Confirme que é a URL de PRODUÇÃO antes de confiar nesse resultado.\n\n";

$db = getDb();
$asaas = new AsaasClient();

$stmt = $db->prepare("
    SELECT p.id, p.numero, p.valor, p.vencimento, p.status, p.asaas_payment_id,
           c.id AS contrato_id, c.descricao_servico, cl.asaas_customer_id, cl.nome AS cliente_nome
    FROM parcelas p
    JOIN contratos c ON c.id = p.contrato_id
    JOIN clientes cl ON cl.id = c.cliente_id
    WHERE p.id = ?
");
$stmt->execute([$parcelaId]);
$parcela = $stmt->fetch();

if (!$parcela) {
    echo "Parcela #$parcelaId não encontrada.\n";
    exit;
}

echo "Parcela #{$parcela['id']} — {$parcela['cliente_nome']} — contrato #{$parcela['contrato_id']} — parcela nº {$parcela['numero']}\n";
echo "Valor: R$ " . number_format((float)$parcela['valor'], 2, ',', '.') . " | Vencimento: {$parcela['vencimento']}\n";
echo "Status atual: {$parcela['status']} | asaas_payment_id atual: " . ($parcela['asaas_payment_id'] ?: '(vazio)') . "\n\n";

if ($parcela['status'] !== 'a_gerar' || $parcela['asaas_payment_id']) {
    echo "PARADO — essa parcela não está 'a_gerar' com asaas_payment_id vazio. Não vou criar cobrança duplicada.\n";
    echo "Se realmente precisa recriar, primeiro resete: UPDATE parcelas SET status='a_gerar', asaas_payment_id=NULL WHERE id=$parcelaId;\n";
    exit;
}

if (!$parcela['asaas_customer_id']) {
    echo "PARADO — esse cliente não tem asaas_customer_id cadastrado.\n";
    exit;
}

$descricao = $parcela['descricao_servico'] ?: "Newsiga — parcela {$parcela['numero']} — contrato #{$parcela['contrato_id']}";
echo "Descrição que será usada: \"$descricao\"\n\n";

if (!$modoReal) {
    echo "SIMULADO — nada foi criado. Adicione &confirmar=sim na URL pra criar de verdade.\n";
    exit;
}

try {
    $cobranca = $asaas->criarCobranca(
        $parcela['asaas_customer_id'],
        (float) $parcela['valor'],
        $parcela['vencimento'],
        "parcela:{$parcela['id']}",
        $descricao
    );
    $db->prepare("UPDATE parcelas SET asaas_payment_id = ?, status = 'gerado' WHERE id = ?")
       ->execute([$cobranca['id'], $parcela['id']]);

    echo "CRIADO com sucesso — novo asaas_payment_id: {$cobranca['id']}\n";
} catch (Throwable $e) {
    echo "ERRO ao criar: " . $e->getMessage() . "\n";
}
