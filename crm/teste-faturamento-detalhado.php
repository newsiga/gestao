<?php
/**
 * Script de teste ISOLADO — só leitura. Mostra, mês a mês, TODAS as
 * linhas (faturas + parcelas) que entram na soma do "Faturamento por
 * mês", uma por uma — pra achar duplicidade/fantasma escondida em vez
 * de só ver o total errado sem saber de onde vem.
 *
 * Uso via navegador:
 *   https://crm.newsiga.com.br/teste-faturamento-detalhado.php?token=SEU_TOKEN
 */

require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

$tokenRecebido = $_GET['token'] ?? '';
if (!defined('ASAAS_WEBHOOK_TOKEN') || !hash_equals(ASAAS_WEBHOOK_TOKEN, $tokenRecebido)) {
    http_response_code(401);
    echo "Token inválido.\n";
    exit;
}

$db = getDb();

for ($i = 5; $i >= 0; $i--) {
    $competencia = date('Y-m', strtotime("-$i months"));
    echo "\n=== $competencia ===\n";

    $stmtFaturas = $db->prepare("
        SELECT f.id, f.contrato_id, cl.nome AS cliente_nome, f.competencia, f.valor, f.vencimento, f.status, f.asaas_payment_id
        FROM faturas f
        JOIN contratos c ON c.id = f.contrato_id
        JOIN clientes cl ON cl.id = c.cliente_id
        WHERE DATE_FORMAT(f.vencimento, '%Y-%m') = ? AND f.status = 'gerado'
        ORDER BY f.vencimento
    ");
    $stmtFaturas->execute([$competencia]);
    $faturas = $stmtFaturas->fetchAll();

    $totalMes = 0;
    foreach ($faturas as $f) {
        echo "  FATURA #{$f['id']} — {$f['cliente_nome']} — contrato #{$f['contrato_id']} — competência {$f['competencia']} — venc {$f['vencimento']} — R$ " . number_format((float)$f['valor'], 2, ',', '.') . " — {$f['asaas_payment_id']}\n";
        $totalMes += (float) $f['valor'];
    }

    $stmtParcelas = $db->prepare("
        SELECT p.id, p.contrato_id, cl.nome AS cliente_nome, p.numero, p.valor, p.vencimento, p.status, p.asaas_payment_id
        FROM parcelas p
        JOIN contratos c ON c.id = p.contrato_id
        JOIN clientes cl ON cl.id = c.cliente_id
        WHERE DATE_FORMAT(p.vencimento, '%Y-%m') = ? AND p.status IN ('gerado', 'pago')
        ORDER BY p.vencimento
    ");
    $stmtParcelas->execute([$competencia]);
    $parcelas = $stmtParcelas->fetchAll();

    foreach ($parcelas as $p) {
        echo "  PARCELA #{$p['id']} — {$p['cliente_nome']} — contrato #{$p['contrato_id']} — nº{$p['numero']} — venc {$p['vencimento']} — R$ " . number_format((float)$p['valor'], 2, ',', '.') . " — status {$p['status']} — {$p['asaas_payment_id']}\n";
        $totalMes += (float) $p['valor'];
    }

    if (empty($faturas) && empty($parcelas)) {
        echo "  (nada)\n";
    }
    echo "  TOTAL DO MÊS: R$ " . number_format($totalMes, 2, ',', '.') . "\n";
}
