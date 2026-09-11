<?php
/**
 * Script de teste ISOLADO — só leitura, não apaga nada. Lista as
 * cobranças em aberto (PENDING) de cada um dos 6 clientes cuja
 * assinatura antiga estamos migrando pro CRM, pra identificar qual
 * cobrança pendente é legítima (mês já coberto) e qual é duplicada
 * (mês que o nosso fechamento também vai cobrar).
 *
 * Uso via navegador:
 *   https://crm.newsiga.com.br/teste-cobrancas-pendentes.php?token=SEU_TOKEN
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

$clientes = $db->query("
    SELECT cl.id, cl.nome, cl.asaas_customer_id
    FROM clientes cl
    JOIN contratos c ON c.cliente_id = cl.id
    WHERE cl.nome IN ('Indústrias Becker', 'Tron Soluções Tecnológicas LTDA', 'Hotel Ocaporã', 'Hotel Tabaobi', 'Hotel Tabapitanga', 'Noronha Pescados')
      AND c.tipo = 'mensalidade_fixa'
    GROUP BY cl.id
")->fetchAll();

foreach ($clientes as $c) {
    echo "\n=== {$c['nome']} ({$c['asaas_customer_id']}) ===\n";
    try {
        $cobrancas = $asaas->listarCobrancasDoCliente($c['asaas_customer_id'], 'PENDING');
        if (empty($cobrancas)) {
            echo "  Nenhuma cobrança PENDING encontrada.\n";
            continue;
        }
        foreach ($cobrancas as $cob) {
            echo "  id: {$cob['id']} | venc: {$cob['dueDate']} | valor: R$ " . number_format((float)$cob['value'], 2, ',', '.');
            echo " | assinatura: " . ($cob['subscription'] ?? '(nenhuma — cobrança avulsa)') . "\n";
        }
    } catch (Throwable $e) {
        echo "  ERRO: " . $e->getMessage() . "\n";
    }
}
