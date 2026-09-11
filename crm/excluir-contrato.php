<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Exclui um contrato de verdade — usado pra corrigir erros de cadastro
 * (cliente errado, teste, duplicado). Diferente do status 'encerrado',
 * isso não deixa rastro nenhum: contrato e parcelas somem do banco.
 *
 * Por segurança, só apaga contratos em 'rascunho' — se já tiver sido
 * aprovado (ou pior, já tiver cobrança gerada no ASAAS), a exclusão é
 * bloqueada aqui. Nesse caso o caminho correto é encerrar, não excluir.
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

if (!$contratoId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Contrato inválido.']);
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

if ($contrato['status'] !== 'rascunho') {
    http_response_code(422);
    echo json_encode([
        'erro' => "Só é possível excluir contratos em 'rascunho'. Este está como '{$contrato['status']}' — use o status 'encerrado' em vez de excluir.",
    ]);
    exit;
}

try {
    $db->beginTransaction();
    $db->prepare('DELETE FROM parcelas WHERE contrato_id = ?')->execute([$contratoId]);
    $db->prepare('DELETE FROM contratos WHERE id = ?')->execute([$contratoId]);
    $db->commit();

    echo json_encode(['sucesso' => true]);
} catch (Throwable $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao excluir contrato', 'detalhe' => $e->getMessage()]);
}
