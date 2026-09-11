<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Lista faturas (tabela `faturas`, gerada pelo fechar-competencia.php),
 * com o nome do cliente e o tipo do contrato — usado pelo "Radar de
 * cobranças" do painel. Traz uma janela razoável (60 dias pra trás, 60
 * pra frente) em vez da tabela inteira, já que ela só cresce com o tempo.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDb();
    $stmt = $db->query("
        SELECT
            f.id, f.contrato_id, f.competencia, f.valor, f.valor_liquido, f.valor_imposto,
            f.vencimento, f.status,
            c.tipo AS contrato_tipo, c.descricao AS contrato_descricao,
            cl.nome AS cliente_nome
        FROM faturas f
        JOIN contratos c ON c.id = f.contrato_id
        JOIN clientes cl ON cl.id = c.cliente_id
        WHERE f.vencimento BETWEEN DATE_SUB(CURDATE(), INTERVAL 180 DAY) AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
        ORDER BY f.vencimento ASC
    ");
    echo json_encode(['sucesso' => true, 'faturas' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao listar faturas', 'detalhe' => $e->getMessage()]);
}
