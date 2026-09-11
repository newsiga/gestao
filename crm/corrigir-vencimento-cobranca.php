<?php
/**
 * Script de EXECUÇÃO — corrige o vencimento de faturas que nasceram com
 * a data errada (bug antigo do montarDataVencimento, já corrigido no
 * fechar-competencia.php, mas essas 4 já tinham sido criadas antes da
 * correção). Corrige no ASAAS de verdade E no nosso banco.
 *
 * Uso via navegador (modo simulação por padrão):
 *   https://crm.newsiga.com.br/corrigir-vencimento-cobranca.php?token=SEU_TOKEN
 * Pra aplicar de verdade:
 *   ...&confirmar=sim
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

// payment_id => [nome, vencimento correto]
$correcoes = [
    'pay_75hq7k217s1qlvx4' => ['Pernambuco Química', '2026-09-10'],
    'pay_yqz3vjq9jehmdby1' => ['Dragão (suporte)', '2026-09-10'],
    'pay_sssl8h9d03qpr7eu' => ['Mari Louças', '2026-09-05'],
    'pay_txr2n5x1rlodi5ah' => ['Fiabesa Guararapes', '2026-09-08'],
];

echo $modoReal ? "=== MODO REAL ===\n\n" : "=== MODO SIMULAÇÃO ===\n\n";

$db = getDb();
$asaas = new AsaasClient();

foreach ($correcoes as $paymentId => [$nome, $vencimentoCorreto]) {
    echo "$nome ($paymentId): ";
    try {
        $atual = $asaas->buscarCobranca($paymentId);
        $vencimentoAtual = $atual['dueDate'] ?? '?';
        $status = $atual['status'] ?? '?';

        if ($status !== 'PENDING') {
            echo "PULADO — status é '$status', não 'PENDING'. Não mexo.\n";
            continue;
        }
        if ($vencimentoAtual === $vencimentoCorreto) {
            echo "já está com a data certa ($vencimentoCorreto), pulando.\n";
            continue;
        }

        if (!$modoReal) {
            echo "SIMULADO — trocaria vencimento de $vencimentoAtual para $vencimentoCorreto.\n";
            continue;
        }

        $asaas->atualizarCobranca($paymentId, ['dueDate' => $vencimentoCorreto]);
        $db->prepare("UPDATE faturas SET vencimento = ? WHERE asaas_payment_id = ?")
           ->execute([$vencimentoCorreto, $paymentId]);

        echo "CORRIGIDO no ASAAS e no banco — vencimento agora é $vencimentoCorreto.\n";
    } catch (Throwable $e) {
        echo "ERRO: " . $e->getMessage() . "\n";
    }
}
