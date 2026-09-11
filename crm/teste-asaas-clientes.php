<?php
/**
 * Script de teste ISOLADO — só leitura, não grava nada no banco nem no
 * ASAAS. Pra cada cliente cadastrado com CNPJ, busca no ASAAS (usando a
 * chave configurada em config-crm.php no momento) o cliente correspondente
 * por CNPJ, e mostra o ID encontrado — pra conferir manualmente antes de
 * atualizar `asaas_customer_id` no banco.
 *
 * IMPORTANTE: só roda isso DEPOIS de trocar ASAAS_API_KEY/ASAAS_BASE_URL
 * em config-crm.php pra produção — senão vai buscar no sandbox de novo.
 *
 * Uso via navegador:
 *   https://crm.newsiga.com.br/teste-asaas-clientes.php?token=SEU_TOKEN
 */

require_once __DIR__ . '/asaas-client.php';

header('Content-Type: text/plain; charset=utf-8');

$tokenRecebido = $_GET['token'] ?? '';
if (!defined('ASAAS_WEBHOOK_TOKEN') || !hash_equals(ASAAS_WEBHOOK_TOKEN, $tokenRecebido)) {
    http_response_code(401);
    echo "Token inválido.\n";
    exit;
}

echo "Base URL do ASAAS configurada agora: " . (defined('ASAAS_BASE_URL') ? ASAAS_BASE_URL : '(não definida)') . "\n";
echo "Confirme que é a URL de PRODUÇÃO antes de confiar nesse resultado.\n\n";

$db = getDb();
$asaas = new AsaasClient();

$clientes = $db->query("SELECT id, nome, cnpj, asaas_customer_id FROM clientes WHERE cnpj IS NOT NULL AND cnpj != '' ORDER BY nome")->fetchAll();

echo "Total de clientes com CNPJ cadastrado: " . count($clientes) . "\n";
echo str_repeat('-', 90) . "\n";

foreach ($clientes as $c) {
    echo "\n[{$c['id']}] {$c['nome']}\n";
    echo "  CNPJ local:              {$c['cnpj']}\n";
    echo "  asaas_customer_id atual: " . ($c['asaas_customer_id'] ?: '(vazio)') . "\n";

    try {
        $encontrado = $asaas->buscarClientePorDocumento($c['cnpj']);
        if ($encontrado) {
            $mudou = ($c['asaas_customer_id'] !== $encontrado['id']) ? '  <<< DIFERENTE DO ATUAL' : '  (igual ao atual)';
            echo "  Encontrado no ASAAS:     id={$encontrado['id']} | nome=\"{$encontrado['name']}\"$mudou\n";
        } else {
            echo "  Encontrado no ASAAS:     NÃO ENCONTRADO — confira o CNPJ ou se o cliente existe lá\n";
        }
    } catch (Throwable $e) {
        echo "  ERRO ao consultar: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat('-', 90) . "\n";
echo "Revise cada linha \"DIFERENTE DO ATUAL\" com atenção antes de aplicar qualquer UPDATE.\n";
