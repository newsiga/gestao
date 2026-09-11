<?php
/**
 * Script de teste ISOLADO — só consulta (GET), nenhum efeito colateral,
 * nenhuma mudança. Mostra o estado ATUAL de uma assinatura direto na
 * API do ASAAS, sem depender da interface visual do painel deles.
 *
 * Uso via navegador:
 *   https://crm.newsiga.com.br/teste-consultar-assinatura.php?subscription_id=sub_XXX&token=SEU_TOKEN
 */

require_once __DIR__ . '/asaas-client.php';

header('Content-Type: text/plain; charset=utf-8');

$tokenRecebido = $_GET['token'] ?? '';
if (!defined('ASAAS_WEBHOOK_TOKEN') || !hash_equals(ASAAS_WEBHOOK_TOKEN, $tokenRecebido)) {
    http_response_code(401);
    echo "Token inválido.\n";
    exit;
}

$subscriptionId = $_GET['subscription_id'] ?? '';
if ($subscriptionId === '') {
    echo "Uso: ?subscription_id=sub_XXX&token=...\n";
    exit;
}

$asaas = new AsaasClient();

try {
    $dados = $asaas->buscarAssinatura($subscriptionId);
    echo "=== Estado ATUAL da assinatura $subscriptionId (consultado agora, direto da API) ===\n\n";
    echo "status:       " . ($dados['status'] ?? '?') . "\n";
    echo "customer:      " . ($dados['customer'] ?? '?') . "\n";
    echo "description:   " . ($dados['description'] ?? '?') . "\n";
    echo "value:         " . ($dados['value'] ?? '?') . "\n";
    echo "nextDueDate:   " . ($dados['nextDueDate'] ?? '?') . "\n";
    echo "deleted:       " . var_export($dados['deleted'] ?? null, true) . "\n";
} catch (Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
