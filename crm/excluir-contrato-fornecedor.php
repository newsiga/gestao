<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Exclui um contrato de fornecedor de verdade — mesma regra de
 * excluir-contrato.php: só em 'rascunho'. Se já tiver despesa de
 * competência lançada, bloqueia também (evita órfãos em despesas_competencia).
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$contratoId = filter_input(INPUT_POST, 'contrato_id', FILTER_VALIDATE_INT);

if (!$contratoId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Contrato inválido.']);
    exit;
}

$stmt = $db->prepare('SELECT status FROM contratos_fornecedor WHERE id = ?');
$stmt->execute([$contratoId]);
$contrato = $stmt->fetch();

if (!$contrato) {
    http_response_code(404);
    echo json_encode(['erro' => 'Contrato de fornecedor não encontrado.']);
    exit;
}

if ($contrato['status'] !== 'rascunho') {
    http_response_code(422);
    echo json_encode([
        'erro' => "Só é possível excluir contratos em 'rascunho'. Este está como '{$contrato['status']}' — use o status 'encerrado' em vez de excluir.",
    ]);
    exit;
}

$checkDespesas = $db->prepare('SELECT COUNT(*) AS total FROM despesas_competencia WHERE contrato_fornecedor_id = ?');
$checkDespesas->execute([$contratoId]);
if ((int) $checkDespesas->fetch()['total'] > 0) {
    http_response_code(422);
    echo json_encode(['erro' => 'Este contrato já tem despesa de competência lançada — use "encerrado" em vez de excluir.']);
    exit;
}

try {
    $db->prepare('DELETE FROM contratos_fornecedor WHERE id = ?')->execute([$contratoId]);
    echo json_encode(['sucesso' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao excluir contrato de fornecedor', 'detalhe' => $e->getMessage()]);
}
