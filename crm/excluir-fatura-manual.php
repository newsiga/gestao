<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Exclui um lançamento manual de receita — só faturas de contrato com
 * faturamento_gerenciado_por = 'manual', e só se ainda não estiver
 * 'pago' (mesma regra de excluir-despesa-competencia.php: um
 * recebimento já confirmado não deve simplesmente sumir do histórico).
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$faturaId = filter_input(INPUT_POST, 'fatura_id', FILTER_VALIDATE_INT);

if (!$faturaId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Fatura inválida.']);
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
    echo json_encode(['erro' => 'Só é possível excluir faturas de contrato com faturamento manual.']);
    exit;
}

if ($fatura['status'] === 'pago') {
    http_response_code(422);
    echo json_encode(['erro' => 'Esta receita já está paga — volte o status pra "confirmado" antes de excluir, se realmente precisar.']);
    exit;
}

try {
    $db->prepare('DELETE FROM faturas WHERE id = ?')->execute([$faturaId]);
    echo json_encode(['sucesso' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao excluir receita', 'detalhe' => $e->getMessage()]);
}
