<?php
require_once __DIR__.'/auth.php'; require_login_api();
/** Exclui um prospect. Sem confirmação adicional aqui — a confirmação acontece no front-end. */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(422);
    echo json_encode(['erro' => 'Prospect inválido.']);
    exit;
}

try {
    $db->prepare('DELETE FROM prospects WHERE id = ?')->execute([$id]);
    echo json_encode(['sucesso' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao excluir prospect', 'detalhe' => $e->getMessage()]);
}
