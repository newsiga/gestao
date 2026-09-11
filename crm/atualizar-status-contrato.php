<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Atualiza o status de um contrato. Usado pela tela de listagem de
 * contratos (crm-newsiga-contratos.html) — por enquanto só troca o
 * campo `status`; quando a integração ASAAS estiver ligada, marcar
 * como 'aprovado' vai ser o gatilho pra abrir a tela de confirmação.
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

$statusValidos = ['rascunho', 'aprovado', 'ativo', 'concluido', 'encerrado'];

// Transições permitidas — evita, por exemplo, voltar um contrato 'ativo'
// direto pra 'rascunho' sem passar por 'encerrado' primeiro.
$transicoesPermitidas = [
    'rascunho'  => ['aprovado', 'encerrado'],
    'aprovado'  => ['rascunho', 'ativo', 'encerrado'],
    'ativo'     => ['concluido', 'encerrado'],
    'concluido' => ['ativo', 'encerrado'], // reabrir, se marcou por engano
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

$stmt = $db->prepare('SELECT status FROM contratos WHERE id = ?');
$stmt->execute([$contratoId]);
$contrato = $stmt->fetch();

if (!$contrato) {
    http_response_code(404);
    echo json_encode(['erro' => 'Contrato não encontrado.']);
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

// TODO: quando a integração ASAAS estiver ligada, é aqui que entra a
// chamada pra prepararConfirmacaoAsaas() quando $novoStatus === 'aprovado'
// — por enquanto, só grava o novo status.
$db->prepare('UPDATE contratos SET status = ? WHERE id = ?')->execute([$novoStatus, $contratoId]);

echo json_encode(['sucesso' => true, 'status' => $novoStatus]);
