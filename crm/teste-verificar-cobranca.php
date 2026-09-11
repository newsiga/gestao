<?php
/**
 * Script de teste ISOLADO — só leitura. Consulta uma cobrança específica
 * pelo id, pra ver a qual customer ela realmente pertence.
 *
 * Uso via navegador:
 *   https://crm.newsiga.com.br/teste-verificar-cobranca.php?payment_id=pay_XXX&token=SEU_TOKEN
 */

require_once __DIR__ . '/asaas-client.php';

header('Content-Type: text/plain; charset=utf-8');

$tokenRecebido = $_GET['token'] ?? '';
if (!defined('ASAAS_WEBHOOK_TOKEN') || !hash_equals(ASAAS_WEBHOOK_TOKEN, $tokenRecebido)) {
    http_response_code(401);
    echo "Token inválido.\n";
    exit;
}

$paymentId = $_GET['payment_id'] ?? '';
if ($paymentId === '') {
    echo "Uso: ?payment_id=pay_XXX&token=...\n";
    exit;
}

$asaas = new AsaasClient();

try {
    $cobranca = $asaas->buscarCobranca($paymentId);
    echo json_encode($cobranca, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
