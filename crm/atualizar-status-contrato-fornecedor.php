<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Atualiza o status de um contrato de fornecedor. Mesmo padrão de
 * atualizar-status-contrato.php, com um status a menos: não existe
 * 'aprovado' aqui, porque não há gatilho de confirmação ASAAS do lado
 * de despesa — o contrato nasce em 'rascunho' e vai direto pra 'ativo'
 * quando estiver pronto pra entrar no lançamento mensal.
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
$novoStatus = $_POST['status'] ?? '';

$statusValidos = ['rascunho', 'ativo', 'concluido', 'encerrado'];

$transicoesPermitidas = [
    'rascunho'  => ['ativo', 'encerrado'],
    'ativo'     => ['concluido', 'encerrado'],
    'concluido' => ['ativo', 'encerrado'],
    'encerrado' => [],
];

if (!$contratoId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Contrato inválido.']);
    exit;
}
if (!in_array($novoStatus, $statusValidos, true)) {
    http_response_code(422);
    echo json_encode(['erro' => 'Status inválido.']);
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

$statusAtual = $contrato['status'];
if ($statusAtual === $novoStatus) {
    echo json_encode(['sucesso' => true, 'status' => $novoStatus, 'sem_alteracao' => true]);
    exit;
}
if (!in_array($novoStatus, $transicoesPermitidas[$statusAtual] ?? [], true)) {
    http_response_code(422);
    echo json_encode(['erro' => "Não é possível mudar de '$statusAtual' para '$novoStatus'."]);
    exit;
}

$db->prepare('UPDATE contratos_fornecedor SET status = ? WHERE id = ?')->execute([$novoStatus, $contratoId]);

echo json_encode(['sucesso' => true, 'status' => $novoStatus]);
