<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Lista despesas de competência com o nome do fornecedor e a descrição
 * do contrato, em JSON — usado pela tela de listagem de despesas
 * (crm-newsiga-despesas.php) e pelo painel (fluxo de caixa consolidado).
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDb();

    $stmt = $db->query("
        SELECT
            dc.id, dc.contrato_fornecedor_id, dc.competencia, dc.horas_consumidas, dc.valor,
            dc.vencimento, dc.status, dc.origem, dc.criado_em,
            cf.tipo AS contrato_tipo, cf.descricao AS contrato_descricao,
            f.id AS fornecedor_id, f.nome AS fornecedor_nome, f.tipo AS fornecedor_tipo
        FROM despesas_competencia dc
        JOIN contratos_fornecedor cf ON cf.id = dc.contrato_fornecedor_id
        JOIN fornecedores f ON f.id = cf.fornecedor_id
        ORDER BY dc.vencimento DESC
    ");
    echo json_encode(['sucesso' => true, 'despesas' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao listar despesas de competência', 'detalhe' => $e->getMessage()]);
}
