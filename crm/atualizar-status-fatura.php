<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Atualiza o status de uma fatura — só pra faturas de contrato com
 * faturamento_gerenciado_por = 'manual' (as geradas pelo sistema/ASAAS
 * mudam de status sozinhas, via webhook — mexer nelas manualmente aqui
 * ficaria dessincronizado do que o ASAAS realmente sabe).
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$faturaId   = filter_input(INPUT_POST, 'fatura_id', FILTER_VALIDATE_INT);
$novoStatus = $_POST['status'] ?? '';

$statusValidos = ['confirmado', 'pago', 'atrasado'];

$transicoesPermitidas = [
    'confirmado' => ['pago', 'atrasado'],
    'atrasado'   => ['pago'],
    'pago'       => ['confirmado'], // reabrir, se marcou por engano
];

if (!$faturaId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Fatura inválida.']);
    exit;
}
if (!in_array($novoStatus, $statusValidos, true)) {
    http_response_code(422);
    echo json_encode(['erro' => 'Status inválido.']);
    exit;
}

$stmt = $db->prepare("
    SELECT f.status, c.faturamento_gerenciado_por
    FROM faturas f JOIN contratos c ON c.id = f.contrato_id
    WHERE f.id = ?
");
$stmt->execute([$faturaId]);
$fatura = $stmt->fetch();

if (!$fatura) {
    http_response_code(404);
    echo json_encode(['erro' => 'Fatura não encontrada.']);
    exit;
}

if ($fatura['faturamento_gerenciado_por'] !== 'manual') {
    http_response_code(422);
    echo json_encode(['erro' => 'Esta fatura é gerenciada pelo sistema (ASAAS) — o status muda sozinho, não dá pra editar manualmente.']);
    exit;
}

$statusAtual = $fatura['status'];
if ($statusAtual === $novoStatus) {
    echo json_encode(['sucesso' => true, 'status' => $novoStatus, 'sem_alteracao' => true]);
    exit;
}
if (!in_array($novoStatus, $transicoesPermitidas[$statusAtual] ?? [], true)) {
    http_response_code(422);
    echo json_encode(['erro' => "Não é possível mudar de '$statusAtual' para '$novoStatus'."]);
    exit;
}

$db->prepare('UPDATE faturas SET status = ? WHERE id = ?')->execute([$novoStatus, $faturaId]);

echo json_encode(['sucesso' => true, 'status' => $novoStatus]);
