<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Retorna uma única despesa de competência (com dados do contrato e
 * fornecedor) em JSON, pra popular a tela de edição. Mesmo padrão de
 * buscar-contrato-fornecedor.php.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$despesaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$despesaId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Id inválido.']);
    exit;
}

try {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT dc.*, cf.tipo AS contrato_tipo, cf.valor_hora, cf.descricao AS contrato_descricao,
               f.nome AS fornecedor_nome
        FROM despesas_competencia dc
        JOIN contratos_fornecedor cf ON cf.id = dc.contrato_fornecedor_id
        JOIN fornecedores f ON f.id = cf.fornecedor_id
        WHERE dc.id = ?
    ");
    $stmt->execute([$despesaId]);
    $despesa = $stmt->fetch();

    if (!$despesa) {
        http_response_code(404);
        echo json_encode(['erro' => 'Despesa não encontrada.']);
        exit;
    }

    echo json_encode(['sucesso' => true, 'despesa' => $despesa], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao buscar despesa', 'detalhe' => $e->getMessage()]);
}
