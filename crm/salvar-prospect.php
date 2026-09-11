<?php
require_once __DIR__.'/auth.php'; require_login_api();
/** Recebe o POST do formulário "Novo prospect" e grava em `prospects`. */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$nome          = trim($_POST['nome'] ?? '');
$contato       = trim($_POST['contato'] ?? '') ?: null;
$email         = trim($_POST['email'] ?? '') ?: null;
$telefone      = trim($_POST['telefone'] ?? '') ?: null;
$descricao     = trim($_POST['descricao'] ?? '') ?: null;
$estagio       = $_POST['estagio'] ?? 'contato_inicial';
$valorEstimado = filter_input(INPUT_POST, 'valor_estimado', FILTER_VALIDATE_FLOAT) ?: null;
$observacoes   = trim($_POST['observacoes'] ?? '') ?: null;
$proximoContato = trim($_POST['proximo_contato'] ?? '') ?: null;
$prioritario   = isset($_POST['prioritario']) ? 1 : 0;

$estagiosValidos = ['contato_inicial', 'em_negociacao', 'proposta_enviada', 'ganho', 'perdido'];

$erros = [];
if ($nome === '') $erros[] = 'Nome da empresa é obrigatório.';
if (!in_array($estagio, $estagiosValidos, true)) $erros[] = 'Estágio inválido.';
if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
if ($proximoContato !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $proximoContato)) $erros[] = 'Data do próximo contato inválida.';

if ($erros) {
    http_response_code(422);
    echo json_encode(['erro' => 'Dados inválidos', 'detalhes' => $erros]);
    exit;
}

try {
    $stmt = $db->prepare('
        INSERT INTO prospects (nome, contato, email, telefone, estagio, descricao, valor_estimado, observacoes, proximo_contato, prioritario)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$nome, $contato, $email, $telefone, $estagio, $descricao, $valorEstimado, $observacoes, $proximoContato, $prioritario]);
    $id = (int) $db->lastInsertId();

    echo json_encode(['sucesso' => true, 'prospect_id' => $id, 'nome' => $nome]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao gravar prospect', 'detalhe' => $e->getMessage()]);
}
