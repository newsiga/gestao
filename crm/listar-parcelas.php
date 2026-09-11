<?php
require_once __DIR__.'/auth.php'; require_login_api();
/** Lista as parcelas de um contrato — usado na tela de edição, seção de projeto parcelado. */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$contratoId = filter_input(INPUT_GET, 'contrato_id', FILTER_VALIDATE_INT);

if (!$contratoId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Contrato inválido.']);
    exit;
}

try {
    $db = getDb();
    $stmt = $db->prepare('SELECT id, numero, valor, vencimento, status, asaas_payment_id FROM parcelas WHERE contrato_id = ? ORDER BY numero');
    $stmt->execute([$contratoId]);
    echo json_encode(['sucesso' => true, 'parcelas' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao listar parcelas', 'detalhe' => $e->getMessage()]);
}
