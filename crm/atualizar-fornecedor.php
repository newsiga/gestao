<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Atualiza um fornecedor existente. Mesmo padrão de atualizar-cliente.php.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$id                     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$nome                   = trim($_POST['nome'] ?? '');
$tipo                   = $_POST['tipo'] ?? '';
$movideskTechnicianName = trim($_POST['movidesk_technician_name'] ?? '') ?: null;
$formaPagamento         = trim($_POST['forma_pagamento'] ?? '') ?: null;
$status                 = $_POST['status'] ?? 'ativo';

$tiposValidos  = ['operacional', 'fixo'];
$statusValidos = ['ativo', 'inativo'];

$erros = [];
if (!$id) $erros[] = 'Fornecedor inválido.';
if ($nome === '') $erros[] = 'Nome é obrigatório.';
if (!in_array($tipo, $tiposValidos, true)) $erros[] = 'Tipo de fornecedor inválido.';
if (!in_array($status, $statusValidos, true)) $erros[] = 'Status inválido.';

if ($erros) {
    http_response_code(422);
    echo json_encode(['erro' => 'Dados inválidos', 'detalhes' => $erros]);
    exit;
}

try {
    $stmt = $db->prepare("
        UPDATE fornecedores
        SET nome = ?, tipo = ?, movidesk_technician_name = ?, forma_pagamento = ?, status = ?
        WHERE id = ?
    ");
    $stmt->execute([$nome, $tipo, $movideskTechnicianName, $formaPagamento, $status, $id]);

    echo json_encode(['sucesso' => true, 'fornecedor_id' => $id, 'nome' => $nome]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao atualizar fornecedor', 'detalhe' => $e->getMessage()]);
}
