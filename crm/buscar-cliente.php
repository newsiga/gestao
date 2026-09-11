<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Retorna um único cliente em JSON, pra popular a tela de detalhe
 * (crm-newsiga-cliente-detalhe.html). Mesmo padrão do buscar-contrato.php.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$clienteId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$clienteId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Id inválido.']);
    exit;
}

try {
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM clientes WHERE id = ?');
    $stmt->execute([$clienteId]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        http_response_code(404);
        echo json_encode(['erro' => 'Cliente não encontrado.']);
        exit;
    }

    echo json_encode(['sucesso' => true, 'cliente' => $cliente], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao buscar cliente', 'detalhe' => $e->getMessage()]);
}
