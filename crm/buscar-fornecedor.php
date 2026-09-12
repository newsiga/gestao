<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Retorna um único fornecedor em JSON, pra popular a tela de edição.
 * Mesmo padrão de buscar-cliente.php.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$fornecedorId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$fornecedorId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Id inválido.']);
    exit;
}

try {
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM fornecedores WHERE id = ?');
    $stmt->execute([$fornecedorId]);
    $fornecedor = $stmt->fetch();

    if (!$fornecedor) {
        http_response_code(404);
        echo json_encode(['erro' => 'Fornecedor não encontrado.']);
        exit;
    }

    echo json_encode(['sucesso' => true, 'fornecedor' => $fornecedor], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao buscar fornecedor', 'detalhe' => $e->getMessage()]);
}
