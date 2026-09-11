<?php
require_once __DIR__.'/auth.php'; require_login_api();
/** Retorna um único prospect em JSON, pra popular o formulário de edição. */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(422);
    echo json_encode(['erro' => 'Id inválido.']);
    exit;
}

try {
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM prospects WHERE id = ?');
    $stmt->execute([$id]);
    $prospect = $stmt->fetch();

    if (!$prospect) {
        http_response_code(404);
        echo json_encode(['erro' => 'Prospect não encontrado.']);
        exit;
    }

    echo json_encode(['sucesso' => true, 'prospect' => $prospect], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao buscar prospect', 'detalhe' => $e->getMessage()]);
}
