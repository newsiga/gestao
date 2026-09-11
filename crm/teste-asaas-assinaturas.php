<?php
/**
 * Script de teste ISOLADO — só leitura, não cancela nada. Verifica se
 * cada cliente já tem alguma assinatura ATIVA no ASAAS (criada fora do
 * nosso sistema, direto no painel deles) — isso explicaria cobranças
 * automáticas de setembro que apareceram sem o fechar-competencia.php
 * ter rodado.
 *
 * Uso via navegador:
 *   https://crm.newsiga.com.br/teste-asaas-assinaturas.php?token=SEU_TOKEN
 */

require_once __DIR__ . '/asaas-client.php';

header('Content-Type: text/plain; charset=utf-8');

$tokenRecebido = $_GET['token'] ?? '';
if (!defined('ASAAS_WEBHOOK_TOKEN') || !hash_equals(ASAAS_WEBHOOK_TOKEN, $tokenRecebido)) {
    http_response_code(401);
    echo "Token inválido.\n";
    exit;
}

$db = getDb();
$asaas = new AsaasClient();

$clientes = $db->query("SELECT id, nome, asaas_customer_id FROM clientes WHERE asaas_customer_id IS NOT NULL ORDER BY nome")->fetchAll();

echo "Verificando assinaturas ativas no ASAAS pra " . count($clientes) . " cliente(s)...\n";
echo str_repeat('-', 90) . "\n";

$totalAssinaturasAtivas = 0;

foreach ($clientes as $c) {
    echo "\n[{$c['id']}] {$c['nome']} (asaas_customer_id: {$c['asaas_customer_id']})\n";

    try {
        $assinaturas = $asaas->listarAssinaturasDoCliente($c['asaas_customer_id']);
        if (empty($assinaturas)) {
            echo "  Nenhuma assinatura encontrada.\n";
            continue;
        }
        foreach ($assinaturas as $assinatura) {
            $ativa = ($assinatura['status'] ?? '') === 'ACTIVE';
            if ($ativa) $totalAssinaturasAtivas++;
            echo "  " . ($ativa ? '>>> ATIVA <<<' : '(status: ' . ($assinatura['status'] ?? '?') . ')') . "\n";
            echo "      id:          {$assinatura['id']}\n";
            echo "      valor:       R$ " . number_format((float)($assinatura['value'] ?? 0), 2, ',', '.') . "\n";
            echo "      ciclo:       " . ($assinatura['cycle'] ?? '?') . "\n";
            echo "      descrição:   " . ($assinatura['description'] ?? '?') . "\n";
            echo "      próx. venc.: " . ($assinatura['nextDueDate'] ?? '?') . "\n";
        }
    } catch (Throwable $e) {
        echo "  ERRO ao consultar: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat('-', 90) . "\n";
echo "Total de assinaturas ATIVAS encontradas: $totalAssinaturasAtivas\n";
if ($totalAssinaturasAtivas > 0) {
    echo "\nATENÇÃO: cliente(s) com assinatura ativa E faturamento_gerenciado_por='sistema'\n";
    echo "vão ser cobrados duas vezes quando o fechar-competencia.php rodar de novo,\n";
    echo "a menos que a assinatura antiga seja suspensa antes.\n";
}
