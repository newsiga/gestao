<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Soma o valor de tudo com VENCIMENTO nos últimos 6 meses corridos —
 * faturas (contratos recorrentes) + parcelas (projetos) juntos, pra
 * refletir o mesmo mês que aparece no Radar de cobranças e na Previsão
 * do mês (por vencimento, não por competência — competência é o mês do
 * serviço, vencimento é quando cai de verdade no ASAAS).
 *
 * Só considera status 'gerado' (cobrança criada no ASAAS de verdade) —
 * 'a_gerar' ainda não é receita confirmada.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDb();

    $stmtFaturas = $db->prepare("SELECT COALESCE(SUM(valor), 0) AS total FROM faturas WHERE DATE_FORMAT(vencimento, '%Y-%m') = ? AND status = 'gerado'");
    $stmtParcelas = $db->prepare("SELECT COALESCE(SUM(valor), 0) AS total FROM parcelas WHERE DATE_FORMAT(vencimento, '%Y-%m') = ? AND status IN ('gerado', 'pago')");

    $linhas = [];
    for ($i = 5; $i >= 0; $i--) {
        $competencia = date('Y-m', strtotime("-$i months"));

        $stmtFaturas->execute([$competencia]);
        $totalFaturas = (float) $stmtFaturas->fetch()['total'];

        $stmtParcelas->execute([$competencia]);
        $totalParcelas = (float) $stmtParcelas->fetch()['total'];

        $linhas[] = ['competencia' => $competencia, 'total' => $totalFaturas + $totalParcelas];
    }

    echo json_encode(['sucesso' => true, 'faturamento' => $linhas], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao calcular faturamento mensal', 'detalhe' => $e->getMessage()]);
}
