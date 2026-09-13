<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Soma o faturamento dos últimos 6 meses (por VENCIMENTO, não
 * competência), usando a MESMA fórmula da "Previsão do mês" do
 * JavaScript do painel (renderKpis, crm-newsiga-painel.php) — senão os
 * dois números batem diferente pro mesmo mês, o que é confuso:
 *
 * - Contrato ativo com fatura real gerada nesse mês: usa o valor da fatura.
 * - Contrato ativo mensalidade_fixa SEM fatura gerada ainda: usa o valor
 *   do contrato mesmo assim — já é certo antes do fechamento rodar.
 * - Contrato por hora (banco_horas_*, hora_aberta) sem fatura ainda: não
 *   entra (consumo do mês não está definido, sem estimativa).
 * - Parcelas de projeto com vencimento no mês: sempre entram pelo valor
 *   real, qualquer status exceto 'pago' (que já foi contabilizado antes).
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDb();

    $contratosAtivos = $db->query("SELECT id, tipo, valor FROM contratos WHERE status = 'ativo'")->fetchAll();

    $stmtFaturasMes = $db->prepare("SELECT contrato_id, valor FROM faturas WHERE DATE_FORMAT(vencimento, '%Y-%m') = ?");
    $stmtParcelasMes = $db->prepare("SELECT COALESCE(SUM(valor), 0) AS total FROM parcelas WHERE DATE_FORMAT(vencimento, '%Y-%m') = ? AND status != 'pago'");

    $linhas = [];
    for ($i = 5; $i >= 0; $i--) {
        $competencia = date('Y-m', strtotime("-$i months"));

        $stmtFaturasMes->execute([$competencia]);
        $faturaPorContrato = [];
        foreach ($stmtFaturasMes->fetchAll() as $f) {
            $faturaPorContrato[$f['contrato_id']] = (float) $f['valor'];
        }

        $total = 0.0;
        foreach ($contratosAtivos as $c) {
            if ($c['tipo'] === 'projeto_parcelado') {
                continue; // parcelas somadas à parte, abaixo
            }
            if (isset($faturaPorContrato[$c['id']])) {
                $total += $faturaPorContrato[$c['id']];
            } elseif ($c['tipo'] === 'mensalidade_fixa') {
                $total += (float) $c['valor'];
            }
        }

        $stmtParcelasMes->execute([$competencia]);
        $total += (float) $stmtParcelasMes->fetch()['total'];

        $linhas[] = ['competencia' => $competencia, 'total' => round($total, 2)];
    }

    echo json_encode(['sucesso' => true, 'faturamento' => $linhas], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao calcular faturamento mensal', 'detalhe' => $e->getMessage()]);
}
