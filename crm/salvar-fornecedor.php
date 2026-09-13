<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Grava um fornecedor novo em `fornecedores`. Mesmo padrão de
 * salvar-cliente.php — sem transação (uma tabela só, sem dependência).
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$nome                    = trim($_POST['nome'] ?? '');
$tipo                    = $_POST['tipo'] ?? '';
$categoria               = trim($_POST['categoria'] ?? '') ?: null;
$movideskTechnicianName  = trim($_POST['movidesk_technician_name'] ?? '') ?: null;
$formaPagamento          = trim($_POST['forma_pagamento'] ?? '') ?: null;

$tiposValidos = ['operacional', 'fixo'];

$erros = [];
if ($nome === '') $erros[] = 'Nome é obrigatório.';
if (!in_array($tipo, $tiposValidos, true)) $erros[] = 'Tipo de fornecedor inválido.';

if ($erros) {
    http_response_code(422);
    echo json_encode(['erro' => 'Dados inválidos', 'detalhes' => $erros]);
    exit;
}

try {
    $stmt = $db->prepare("
        INSERT INTO fornecedores (nome, tipo, categoria, movidesk_technician_name, forma_pagamento)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$nome, $tipo, $categoria, $movideskTechnicianName, $formaPagamento]);
    $fornecedorId = (int) $db->lastInsertId();

    echo json_encode(['sucesso' => true, 'fornecedor_id' => $fornecedorId, 'nome' => $nome]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao gravar fornecedor', 'detalhe' => $e->getMessage()]);
}
