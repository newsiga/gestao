<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Retorna um único contrato (com nome do cliente) em JSON,
 * pra popular o formulário de edição.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$contratoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$contratoId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Id inválido.']);
    exit;
}

try {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT c.*, cl.nome AS cliente_nome, cl.cnpj AS cliente_cnpj
        FROM contratos c
        JOIN clientes cl ON cl.id = c.cliente_id
        WHERE c.id = ?
    ");
    $stmt->execute([$contratoId]);
    $contrato = $stmt->fetch();

    if (!$contrato) {
        http_response_code(404);
        echo json_encode(['erro' => 'Contrato não encontrado.']);
        exit;
    }

    echo json_encode(['sucesso' => true, 'contrato' => $contrato], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao buscar contrato', 'detalhe' => $e->getMessage()]);
}
