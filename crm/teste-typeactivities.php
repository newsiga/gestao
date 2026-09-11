<?php
/**
 * Script de teste ISOLADO — só leitura, não faz parte do sistema, não
 * grava nada no banco nem no ASAAS. Serve só pra ver o que a API do
 * Movidesk devolve no campo typeActivities de um contrato de horas, e
 * comparar com o que aparece na tela do Movidesk (franquia, R$/h,
 * R$/h excedente por atividade/tipo de hora).
 *
 * Uso via navegador:
 *   https://crm.newsiga.com.br/teste-typeactivities.php?nome=NOME_DO_CONTRATO&token=SEU_TOKEN
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
if ($nomeContrato === '') {
    echo "Uso: ?nome=NOME_DO_CONTRATO&token=...\n";
    exit(1);
}

try {
    $dados = movidesk_get('timeAgreement', [
        'name' => $nomeContrato,
        '$expand' => 'typeActivities',
    ]);
    if (isset($dados[0]) && is_array($dados[0])) {
        $dados = $dados[0];
    }
} catch (Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=== Contrato: $nomeContrato ===\n";
echo "differentiateHoursFranchise: " . var_export($dados['differentiateHoursFranchise'] ?? null, true) . "\n";
echo "contractedHours (franquia geral, só usada quando NÃO diferenciado): " . var_export($dados['contractedHours'] ?? null, true) . "\n";
echo "baseAmount (valor base avulso do contrato): " . var_export($dados['baseAmount'] ?? null, true) . "\n";
echo "renewalDay: " . var_export($dados['renewalDay'] ?? null, true) . "\n";
echo "\n--- typeActivities ---\n";

$typeActivities = $dados['typeActivities'] ?? [];
if (empty($typeActivities)) {
    echo "(vazio — nenhuma atividade configurada)\n";
} else {
    foreach ($typeActivities as $i => $ta) {
        echo "\n[$i]\n";
        echo "  activity:            " . var_export($ta['activity'] ?? null, true) . "\n";
        echo "  workingTimeType:     " . var_export($ta['workingTimeType'] ?? null, true) . "\n";
        echo "  franchise:           " . var_export($ta['franchise'] ?? null, true) . "\n";
        echo "  value (R\$/h normal): " . var_export($ta['value'] ?? null, true) . "\n";
        echo "  valueExceededHour:   " . var_export($ta['valueExceededHour'] ?? null, true) . "\n";
        echo "  allowHoursExcedent:  " . var_export($ta['allowHoursExcedent'] ?? null, true) . "\n";
        echo "  shootdownContract:   " . var_export($ta['shootdownContract'] ?? null, true) . "\n";
    }
}

echo "\n--- JSON bruto completo (pra conferência) ---\n";
echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
