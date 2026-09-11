<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Atualiza um cliente existente. Mesma validação do salvar-cliente.php,
 * checando duplicidade de CNPJ excluindo o próprio registro.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$id    = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$nome  = trim($_POST['nome'] ?? '');
$cnpj  = trim($_POST['cnpj'] ?? '') ?: null;
$email = trim($_POST['email'] ?? '') ?: null;

$erros = [];
if (!$id) $erros[] = 'Cliente inválido.';
if ($nome === '') $erros[] = 'Nome é obrigatório.';
if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';

if ($cnpj) {
    $check = $db->prepare('SELECT id FROM clientes WHERE cnpj = ? AND id != ?');
    $check->execute([$cnpj, $id]);
    if ($check->fetch()) {
        $erros[] = 'Já existe outro cliente cadastrado com esse CNPJ.';
    }
}

if ($erros) {
    http_response_code(422);
    echo json_encode(['erro' => 'Dados inválidos', 'detalhes' => $erros]);
    exit;
}

try {
    $stmt = $db->prepare('UPDATE clientes SET nome = ?, cnpj = ?, email = ? WHERE id = ?');
    $stmt->execute([$nome, $cnpj, $email, $id]);

    echo json_encode(['sucesso' => true, 'cliente_id' => $id, 'nome' => $nome]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao atualizar cliente', 'detalhe' => $e->getMessage()]);
}
