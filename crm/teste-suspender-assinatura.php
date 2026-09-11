<?php
/**
 * Script de teste ISOLADO — testa a suspensão de uma assinatura
 * específica, mostrando o erro real do ASAAS se houver, em vez de
 * deixar ele passar silencioso.
 *
 * Uso via navegador:
 *   https://crm.newsiga.com.br/teste-suspender-assinatura.php?subscription_id=sub_XXX&token=SEU_TOKEN
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

echo "Tentando suspender: $subscriptionId\n\n";

try {
    $resultado = $asaas->suspenderAssinatura($subscriptionId);
    echo "SUCESSO. Resposta do ASAAS:\n";
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
