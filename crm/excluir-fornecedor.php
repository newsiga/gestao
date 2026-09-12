<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Exclui um fornecedor de verdade — só permitido se ele nunca teve
 * nenhum contrato de fornecedor associado (nem rascunho). Diferente de
 * excluir-contrato.php (que libera 'rascunho'), aqui a barra é mais alta
 * porque excluir o fornecedor também apagaria o histórico de despesas
 * de qualquer contrato ligado a ele — pra corrigir cadastro errado, é
 * mais seguro marcar `status = 'inativo'` via atualizar-fornecedor.php.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$fornecedorId = filter_input(INPUT_POST, 'fornecedor_id', FILTER_VALIDATE_INT);

if (!$fornecedorId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Fornecedor inválido.']);
    exit;
}

$stmt = $db->prepare('SELECT id FROM fornecedores WHERE id = ?');
$stmt->execute([$fornecedorId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['erro' => 'Fornecedor não encontrado.']);
    exit;
}

$checkContratos = $db->prepare('SELECT COUNT(*) AS total FROM contratos_fornecedor WHERE fornecedor_id = ?');
$checkContratos->execute([$fornecedorId]);
if ((int) $checkContratos->fetch()['total'] > 0) {
    http_response_code(422);
    echo json_encode(['erro' => 'Este fornecedor já tem contrato(s) associado(s) — use "inativo" em vez de excluir.']);
    exit;
}

try {
    $db->prepare('DELETE FROM fornecedores WHERE id = ?')->execute([$fornecedorId]);
    echo json_encode(['sucesso' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao excluir fornecedor', 'detalhe' => $e->getMessage()]);
}
