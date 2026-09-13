<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Lista fornecedores com um resumo de contratos — usado tanto pelo
 * seletor de fornecedor no formulário de cadastro de contrato quanto
 * pela tela de listagem (crm-newsiga-fornecedores.php). Mesmo padrão de
 * listar-clientes.php.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDb();
    $stmt = $db->query("
        SELECT
            f.id, f.nome, f.tipo, f.categoria, f.movidesk_technician_name, f.forma_pagamento, f.status,
            (SELECT COUNT(*) FROM contratos_fornecedor cf WHERE cf.fornecedor_id = f.id AND cf.status != 'encerrado') AS contratos_ativos,
            (SELECT COUNT(*) FROM contratos_fornecedor cf WHERE cf.fornecedor_id = f.id) AS contratos_total
        FROM fornecedores f
        ORDER BY f.nome
    ");
    echo json_encode(['sucesso' => true, 'fornecedores' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao listar fornecedores', 'detalhe' => $e->getMessage()]);
}
