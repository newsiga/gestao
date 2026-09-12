<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Retorna um único contrato de fornecedor (com nome do fornecedor) em
 * JSON. Mesmo padrão de buscar-contrato.php.
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
        SELECT cf.*, f.nome AS fornecedor_nome, f.movidesk_technician_name
        FROM contratos_fornecedor cf
        JOIN fornecedores f ON f.id = cf.fornecedor_id
        WHERE cf.id = ?
    ");
    $stmt->execute([$contratoId]);
    $contrato = $stmt->fetch();

    if (!$contrato) {
        http_response_code(404);
        echo json_encode(['erro' => 'Contrato de fornecedor não encontrado.']);
        exit;
    }

    echo json_encode(['sucesso' => true, 'contrato' => $contrato], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao buscar contrato de fornecedor', 'detalhe' => $e->getMessage()]);
}
