<?php
/**
 * Script de teste ISOLADO — só leitura, não muda nada. Confere, uma por
 * uma, todas as parcelas com asaas_payment_id preenchido nos contratos
 * indicados — testando se a cobrança existe de verdade no ASAAS
 * (evita repetir o caso do contrato #16, que tinha payment_id fantasma).
 *
 * Uso via navegador:
 *   https://crm.newsiga.com.br/teste-verificar-parcelas-fantasma.php?token=SEU_TOKEN
 */

require_once __DIR__ . '/asaas-client.php';

header('Content-Type: text/plain; charset=utf-8');

$tokenRecebido = $_GET['token'] ?? '';
if (!defined('ASAAS_WEBHOOK_TOKEN') || !hash_equals(ASAAS_WEBHOOK_TOKEN, $tokenRecebido)) {
    http_response_code(401);
    echo "Token inválido.\n";
    exit;
}

$contratoIds = [12, 13, 15];

$db = getDb();
$asaas = new AsaasClient();

$stmt = $db->prepare("
    SELECT p.id, p.contrato_id, p.numero, p.valor, p.vencimento, p.status, p.asaas_payment_id, cl.nome AS cliente_nome
    FROM parcelas p
    JOIN contratos c ON c.id = p.contrato_id
    JOIN clientes cl ON cl.id = c.cliente_id
    WHERE p.contrato_id IN (" . implode(',', array_fill(0, count($contratoIds), '?')) . ")
    ORDER BY p.contrato_id, p.numero
");
$stmt->execute($contratoIds);
$parcelas = $stmt->fetchAll();

echo "Total de parcelas encontradas nesses contratos: " . count($parcelas) . "\n";
echo str_repeat('-', 90) . "\n";

$fantasmas = 0;

foreach ($parcelas as $p) {
    echo "\ncontrato #{$p['contrato_id']} ({$p['cliente_nome']}) — parcela nº{$p['numero']} — R$ " . number_format((float)$p['valor'], 2, ',', '.') . " — status: {$p['status']}\n";

    if (!$p['asaas_payment_id']) {
        echo "  (sem asaas_payment_id — nada a checar)\n";
        continue;
    }

    echo "  asaas_payment_id: {$p['asaas_payment_id']} — checando... ";
    try {
        $cobranca = $asaas->buscarCobranca($p['asaas_payment_id']);
        echo "OK, existe de verdade (status ASAAS: " . ($cobranca['status'] ?? '?') . ")\n";
    } catch (Throwable $e) {
        echo "FANTASMA — " . $e->getMessage() . "\n";
        $fantasmas++;
    }
}

echo "\n" . str_repeat('-', 90) . "\n";
echo "Total de parcelas fantasma encontradas: $fantasmas\n";
