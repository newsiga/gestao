<?php
/**
 * Script de teste ISOLADO — só leitura, não grava nada. Testa o motor
 * novo (movidesk_valor_diferenciado) contra um contrato de taxa
 * diferenciada, pra comparar com o relatório oficial antes de confiar
 * nele no fechar-competencia.php.
 *
 * Uso via navegador:
 *   https://crm.newsiga.com.br/teste-diferenciado.php?nome=NOME_DO_CONTRATO&competencia=2026-08&token=SEU_TOKEN
 */

require_once __DIR__ . '/asaas-client.php';
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
    $definicao = movidesk_definicao_contrato($nomeContrato);
    $diferenciado = movidesk_usa_taxa_diferenciada($nomeContrato);

    echo "=== $nomeContrato — competência $competencia ===\n";
    echo "differentiateHoursFranchise: " . var_export($diferenciado, true) . "\n\n";

    $inicio = "$competencia-01T00:00:00";
    $fim = date('Y-m-t', strtotime("$competencia-01")) . 'T23:59:59';
    $dados = movidesk_get('timeAgreementConsumption', [
        'name' => $nomeContrato,
        'startPeriod' => $inicio,
        'endPeriod' => $fim,
    ]);
    if (isset($dados[0]) && is_array($dados[0])) {
        $dados = $dados[0];
    }
    $apontamentos = $dados['timeAppointments'] ?? [];
    $tickets = movidesk_agrupar_tickets($apontamentos);

    if ($diferenciado) {
        [$ticketsComValor, $total, $regras, $totalPorTipo] = movidesk_valores_diferenciados($tickets, $definicao);

        echo "--- Regras por tipo de hora ---\n";
        foreach ($regras as $tipo => $r) {
            echo "$tipo: franquia={$r['franchise']}h, R\$/h={$r['rate']}, R\$/h excedente={$r['excess_rate']}\n";
        }

        echo "\n--- Valor por ticket ---\n";
        foreach ($ticketsComValor as $t) {
            echo "#{$t['number']} ({$t['date']}) — " . round($t['hours'], 4) . "h — R$ " . number_format($t['value'], 2, ',', '.') . "\n";
        }

        echo "\n--- Total por tipo de hora ---\n";
        foreach ($totalPorTipo as $tipo => $valor) {
            echo "$tipo: R$ " . number_format($valor, 2, ',', '.') . "\n";
        }

        echo "\n=== TOTAL CALCULADO: R$ " . number_format($total, 2, ',', '.') . " ===\n";
    } else {
        echo "Esse contrato NÃO usa taxa diferenciada — não é o caso que esse script testa.\n";
        echo "Horas totais no período: " . array_sum(array_column($tickets, 'hours')) . "\n";
    }
} catch (Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
