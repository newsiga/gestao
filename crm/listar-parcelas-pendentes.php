<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Lista parcelas de projeto ainda não pagas, com o nome do cliente e a
 * descrição do contrato — usado pelo KPI "Projetos sem fatura" do
 * painel (parcelas com status 'a_gerar' e vencimento já passado).
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDb();
    $stmt = $db->query("
        SELECT
            p.id, p.contrato_id, p.numero, p.valor, p.vencimento, p.status,
            c.descricao AS contrato_descricao,
            cl.nome AS cliente_nome
        FROM parcelas p
        JOIN contratos c ON c.id = p.contrato_id
        JOIN clientes cl ON cl.id = c.cliente_id
        WHERE p.status != 'pago'
        ORDER BY p.vencimento ASC
    ");
    echo json_encode(['sucesso' => true, 'parcelas' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao listar parcelas pendentes', 'detalhe' => $e->getMessage()]);
}
