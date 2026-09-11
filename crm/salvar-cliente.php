<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Recebe o POST do formulário "Novo cliente" e grava em `clientes`.
 * asaas_customer_id e movidesk_organization ficam de fora por enquanto —
 * são preenchidos mais à frente, quando a integração ASAAS/Movidesk entrar.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$nome  = trim($_POST['nome'] ?? '');
$cnpj  = trim($_POST['cnpj'] ?? '') ?: null;
$email = trim($_POST['email'] ?? '') ?: null;

$erros = [];
if ($nome === '') $erros[] = 'Nome é obrigatório.';
if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';

if ($cnpj) {
    $check = $db->prepare('SELECT id FROM clientes WHERE cnpj = ?');
    $check->execute([$cnpj]);
    if ($check->fetch()) {
        $erros[] = 'Já existe um cliente cadastrado com esse CNPJ.';
    }
}

if ($erros) {
    http_response_code(422);
    echo json_encode(['erro' => 'Dados inválidos', 'detalhes' => $erros]);
    exit;
}

try {
    $stmt = $db->prepare('INSERT INTO clientes (nome, cnpj, email) VALUES (?, ?, ?)');
    $stmt->execute([$nome, $cnpj, $email]);
    $clienteId = (int) $db->lastInsertId();

    echo json_encode(['sucesso' => true, 'cliente_id' => $clienteId, 'nome' => $nome]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao gravar cliente', 'detalhe' => $e->getMessage()]);
}
