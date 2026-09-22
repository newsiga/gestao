<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Retorna uma única fatura manual (com dados do contrato e cliente) em
 * JSON, pra popular a tela de edição. Mesmo padrão de
 * buscar-despesa-competencia.php.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$faturaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$faturaId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Id inválido.']);
    exit;
}

try {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT f.*, c.tipo AS contrato_tipo, c.valor_hora, c.descricao AS contrato_descricao,
               c.faturamento_gerenciado_por, cl.nome AS cliente_nome
        FROM faturas f
        JOIN contratos c ON c.id = f.contrato_id
        JOIN clientes cl ON cl.id = c.cliente_id
        WHERE f.id = ?
    ");
    $stmt->execute([$faturaId]);
    $fatura = $stmt->fetch();

    if (!$fatura) {
        http_response_code(404);
        echo json_encode(['erro' => 'Fatura não encontrada.']);
        exit;
    }

    echo json_encode(['sucesso' => true, 'fatura' => $fatura], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao buscar fatura', 'detalhe' => $e->getMessage()]);
}
