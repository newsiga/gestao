<?php
/**
 * Script de teste isolado — NÃO faz parte do sistema, é só pra comparar
 * o que a API do Movidesk devolve num mês fechado com o relatório PDF
 * equivalente. Apaga depois de usar.
 *
 * Uso via navegador:
 *   https://crm.newsiga.com.br/teste-valor-agreement.php?nome=NOME_DO_CONTRATO&competencia=2026-07&token=SEU_TOKEN
 * (usa o mesmo token do ASAAS_WEBHOOK_TOKEN já usado no fechar-competencia.php)
 */

require_once __DIR__ . '/asaas-client.php'; // só pra ter acesso à constante ASAAS_WEBHOOK_TOKEN
require_once __DIR__ . '/movidesk-client.php';

header('Content-Type: text/plain; charset=utf-8');

$tokenRecebido = $_GET['token'] ?? '';
if (!defined('ASAAS_WEBHOOK_TOKEN') || !hash_equals(ASAAS_WEBHOOK_TOKEN, $tokenRecebido)) {
    http_response_code(401);
    echo "Token inválido.\n";
    exit;
}

$nomeContrato = $_GET['nome'] ?? '';
$competencia  = $_GET['competencia'] ?? '';

if ($nomeContrato === '' || !preg_match('/^\d{4}-\d{2}$/', $competencia)) {
    echo "Uso: ?nome=NOME_DO_CONTRATO&competencia=AAAA-MM&token=...\n";
    exit(1);
}

try {
    $resultado = movidesk_valor_agreement($nomeContrato, $competencia);
} catch (Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=== $nomeContrato — competência $competencia ===\n";
echo "baseAmount:         R$ " . number_format($resultado['baseAmount'], 2, ',', '.') . "\n";
echo "exceededHourAmount: R$ " . number_format($resultado['exceededHourAmount'], 2, ',', '.') . "\n";
echo "discount:           " . $resultado['discount'] . " (tipo: " . var_export($resultado['discountType'], true) . ")\n";
echo "TOTAL:               R$ " . number_format($resultado['total'], 2, ',', '.') . "\n";
echo "período fechado (id > 0)? " . ($resultado['fechado'] ? 'SIM' : 'NÃO') . "\n";
