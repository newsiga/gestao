<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Lista clientes com um resumo de contratos — usado tanto pelo seletor
 * de cliente no formulário de cadastro de contrato (que só precisa de
 * id/nome/cnpj/asaas_customer_id) quanto pela tela de listagem de
 * clientes (crm-newsiga-clientes.html), que também mostra e-mail e
 * quantos contratos ativos cada um tem.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDb();
    $stmt = $db->query("
        SELECT
            cl.id, cl.nome, cl.cnpj, cl.email, cl.asaas_customer_id,
            (SELECT COUNT(*) FROM contratos c WHERE c.cliente_id = cl.id AND c.status != 'encerrado') AS contratos_ativos,
            (SELECT COUNT(*) FROM contratos c WHERE c.cliente_id = cl.id) AS contratos_total
        FROM clientes cl
        ORDER BY cl.nome
    ");
    echo json_encode(['sucesso' => true, 'clientes' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao listar clientes', 'detalhe' => $e->getMessage()]);
}
