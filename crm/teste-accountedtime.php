<?php
/**
 * Script de teste ISOLADO — só leitura. Verifica se o campo accountedTime
 * (tempo contabilizado) aparece nos apontamentos retornados pelo
 * timeAgreementConsumption, ou se só existe na API de Tickets.
 *
 * Uso via navegador:
 *   https://crm.newsiga.com.br/teste-accountedtime.php?nome=NOME_DO_CONTRATO&competencia=2026-08&token=SEU_TOKEN
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

$inicio = "$competencia-01T00:00:00";
$fim = date('Y-m-t', strtotime("$competencia-01")) . 'T23:59:59';

try {
    $dados = movidesk_get('timeAgreementConsumption', [
        'name' => $nomeContrato,
        'startPeriod' => $inicio,
        'endPeriod' => $fim,
    ]);
    if (isset($dados[0]) && is_array($dados[0])) {
        $dados = $dados[0];
    }
} catch (Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

$apontamentos = $dados['timeAppointments'] ?? [];
echo "Total de apontamentos: " . count($apontamentos) . "\n\n";

$temAccountedTime = false;
$somaWorkTime = 0.0;
$somaAccountedTime = 0.0;

foreach ($apontamentos as $ap) {
    $temCampo = array_key_exists('accountedTime', $ap);
    if ($temCampo) $temAccountedTime = true;

    echo "Ticket {$ap['ticketNumber']} ação {$ap['actionNumber']} — ";
    echo "workTime: " . ($ap['workTime'] ?? 'N/A');
    echo " | accountedTime: " . ($temCampo ? var_export($ap['accountedTime'], true) : '(CAMPO NÃO EXISTE)');
    echo "\n";

    // soma workTime em horas
    $partes = array_map('intval', explode(':', $ap['workTime'] ?? '00:00:00'));
    $somaWorkTime += ($partes[0] ?? 0) + (($partes[1] ?? 0) / 60) + (($partes[2] ?? 0) / 3600);
    if ($temCampo) $somaAccountedTime += (float) $ap['accountedTime'];
}

echo "\n=== RESUMO ===\n";
echo "Campo accountedTime existe neste endpoint? " . ($temAccountedTime ? 'SIM' : 'NÃO') . "\n";
echo "Soma workTime (o que usamos hoje): " . round($somaWorkTime, 4) . "h\n";
if ($temAccountedTime) {
    echo "Soma accountedTime (o que deveríamos usar): " . round($somaAccountedTime, 4) . "h\n";
}

echo "\n--- Primeiro apontamento (JSON bruto completo, pra ver todos os campos disponíveis) ---\n";
if (!empty($apontamentos)) {
    echo json_encode($apontamentos[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
