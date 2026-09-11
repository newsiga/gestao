<?php
/**
 * Script de teste ISOLADO — só leitura. Versão genérica do
 * teste-cobrancas-pendentes.php, aceita qualquer customer_id via URL,
 * pra reconferir casos avulsos sem precisar editar a lista fixa toda vez.
 *
 * Uso via navegador:
 *   https://crm.newsiga.com.br/teste-cobrancas-cliente.php?customer_id=cus_XXX&token=SEU_TOKEN
 */

require_once __DIR__ . '/asaas-client.php';

header('Content-Type: text/plain; charset=utf-8');

$tokenRecebido = $_GET['token'] ?? '';
if (!defined('ASAAS_WEBHOOK_TOKEN') || !hash_equals(ASAAS_WEBHOOK_TOKEN, $tokenRecebido)) {
    http_response_code(401);
    echo "Token inválido.\n";
    exit;
}

$customerId = $_GET['customer_id'] ?? '';
if ($customerId === '') {
    echo "Uso: ?customer_id=cus_XXX&token=...\n";
    exit;
}

$asaas = new AsaasClient();

try {
    $cobrancas = $asaas->listarCobrancasDoCliente($customerId, 'PENDING');
    if (empty($cobrancas)) {
        echo "Nenhuma cobrança PENDING encontrada pra $customerId.\n";
        exit;
    }
    foreach ($cobrancas as $cob) {
        echo "id: {$cob['id']} | venc: {$cob['dueDate']} | valor: R$ " . number_format((float)$cob['value'], 2, ',', '.');
        echo " | descrição: " . ($cob['description'] ?? '?');
        echo " | assinatura: " . ($cob['subscription'] ?? '(nenhuma — cobrança avulsa)') . "\n";
    }
} catch (Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
