<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Exclui um lançamento de despesa de competência — pra corrigir um
 * lançamento manual errado (valor errado, competência duplicada).
 * Só permite excluir lançamentos com origem = 'manual': os automáticos
 * (fase 2, cálculo via Movidesk) devem ser corrigidos re-rodando o
 * cálculo, não apagados à mão. Também bloqueia despesas já 'pago' —
 * um pagamento já feito não deve simplesmente sumir do histórico; se
 * foi lançado errado, corrija com atualizar-despesa-competencia.php ou
 * volte o status pra 'a_pagar' antes de excluir.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$despesaId = filter_input(INPUT_POST, 'despesa_id', FILTER_VALIDATE_INT);

if (!$despesaId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Despesa inválida.']);
    exit;
}

$stmt = $db->prepare('SELECT origem, status FROM despesas_competencia WHERE id = ?');
$stmt->execute([$despesaId]);
$despesa = $stmt->fetch();

if (!$despesa) {
    http_response_code(404);
    echo json_encode(['erro' => 'Despesa não encontrada.']);
    exit;
}

if ($despesa['origem'] !== 'manual') {
    http_response_code(422);
    echo json_encode(['erro' => 'Só é possível excluir lançamentos manuais.']);
    exit;
}

if ($despesa['status'] === 'pago') {
    http_response_code(422);
    echo json_encode(['erro' => 'Esta despesa já está paga — volte o status pra "a pagar" antes de excluir, se realmente precisar.']);
    exit;
}

try {
    $db->prepare('DELETE FROM despesas_competencia WHERE id = ?')->execute([$despesaId]);
    echo json_encode(['sucesso' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao excluir despesa', 'detalhe' => $e->getMessage()]);
}
