<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Atualiza o status de pagamento de uma despesa de competência
 * (a_pagar -> pago/atrasado, atrasado -> pago). Mesmo espírito das
 * transições de atualizar-status-contrato.php, adaptado ao ciclo de
 * pagamento em vez de ciclo de vida de contrato.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$despesaId  = filter_input(INPUT_POST, 'despesa_id', FILTER_VALIDATE_INT);
$novoStatus = $_POST['status'] ?? '';

$statusValidos = ['a_pagar', 'pago', 'atrasado'];

$transicoesPermitidas = [
    'a_pagar'  => ['pago', 'atrasado'],
    'atrasado' => ['pago'],
    'pago'     => ['a_pagar'], // reabrir, se marcou por engano
];

if (!$despesaId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Despesa inválida.']);
    exit;
}
if (!in_array($novoStatus, $statusValidos, true)) {
    http_response_code(422);
    echo json_encode(['erro' => 'Status inválido.']);
    exit;
}

$stmt = $db->prepare('SELECT status FROM despesas_competencia WHERE id = ?');
$stmt->execute([$despesaId]);
$despesa = $stmt->fetch();

if (!$despesa) {
    http_response_code(404);
    echo json_encode(['erro' => 'Despesa não encontrada.']);
    exit;
}

$statusAtual = $despesa['status'];
if ($statusAtual === $novoStatus) {
    echo json_encode(['sucesso' => true, 'status' => $novoStatus, 'sem_alteracao' => true]);
    exit;
}
if (!in_array($novoStatus, $transicoesPermitidas[$statusAtual] ?? [], true)) {
    http_response_code(422);
    echo json_encode(['erro' => "Não é possível mudar de '$statusAtual' para '$novoStatus'."]);
    exit;
}

$db->prepare('UPDATE despesas_competencia SET status = ? WHERE id = ?')->execute([$novoStatus, $despesaId]);

echo json_encode(['sucesso' => true, 'status' => $novoStatus]);
