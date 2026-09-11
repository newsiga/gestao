<?php
/**
 * Script de EXECUÇÃO ÚNICA — exclui as 6 cobranças duplicadas
 * identificadas manualmente (assinatura antiga + CRM cobrando o mesmo
 * mês). Cada exclusão só acontece depois de confirmar, direto na API,
 * que a cobrança está mesmo PENDING e com vencimento em outubro/2026 —
 * nunca exclui cega, mesmo que a lista abaixo tenha algum erro de
 * digitação.
 *
 * Uso via navegador (uma vez só):
 *   https://crm.newsiga.com.br/excluir-cobrancas-duplicadas.php?token=SEU_TOKEN&confirmar=sim
 * Sem &confirmar=sim, roda em modo simulação (não exclui nada, só mostra
 * o que faria).
 */

require_once __DIR__ . '/asaas-client.php';

header('Content-Type: text/plain; charset=utf-8');

$tokenRecebido = $_GET['token'] ?? '';
if (!defined('ASAAS_WEBHOOK_TOKEN') || !hash_equals(ASAAS_WEBHOOK_TOKEN, $tokenRecebido)) {
    http_response_code(401);
    echo "Token inválido.\n";
    exit;
}

$modoReal = ($_GET['confirmar'] ?? '') === 'sim';

$cobrancasParaExcluir = [
    'pay_nwhmnbpu5ccng5re' => 'Dragão (implantação SIGAGPE/Ponto)',
];

echo $modoReal ? "=== MODO REAL — vai excluir de verdade ===\n\n" : "=== MODO SIMULAÇÃO — nada será excluído ===\n\n";

$asaas = new AsaasClient();

foreach ($cobrancasParaExcluir as $paymentId => $nomeCliente) {
    echo "$nomeCliente ($paymentId): ";
    try {
        $cobranca = $asaas->buscarCobranca($paymentId);

        $status = $cobranca['status'] ?? '?';
        $vencimento = $cobranca['dueDate'] ?? '?';
        $mesVencimento = substr($vencimento, 0, 7);

        if ($status !== 'PENDING') {
            echo "PULADO — status é '$status', não 'PENDING'. Não mexo.\n";
            continue;
        }
        if ($mesVencimento !== '2026-10') {
            echo "PULADO — vencimento é '$vencimento', esperado outubro/2026. Não mexo.\n";
            continue;
        }

        if (!$modoReal) {
            echo "SIMULADO — excluiria (status=$status, venc=$vencimento, valor=R$ " . number_format((float)($cobranca['value'] ?? 0), 2, ',', '.') . ").\n";
            continue;
        }

        $asaas->excluirCobranca($paymentId);
        echo "EXCLUÍDO com sucesso.\n";
    } catch (Throwable $e) {
        echo "ERRO: " . $e->getMessage() . "\n";
    }
}
