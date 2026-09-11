<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Retorna a lista de contratos ativos/aprovados, com o nome do cliente,
 * em JSON — pra o painel (crm-newsiga-painel.html), a tela de contratos
 * e a tela de detalhe do cliente (que filtra por cliente_id no front)
 * consumirem via fetch.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDb();

    $stmt = $db->query("
        SELECT
            c.id, c.cliente_id, c.tipo, c.descricao, c.valor, c.valor_hora, c.valor_hora_excedente, c.horas_banco, c.dia_vencimento,
            c.status, c.faturamento_gerenciado_por, c.criado_em,
            cl.nome AS cliente_nome,
            (SELECT SUM(p.valor) FROM parcelas p WHERE p.contrato_id = c.id) AS valor_parcelas_total,
            (SELECT SUM(p.valor) FROM parcelas p WHERE p.contrato_id = c.id AND p.status != 'pago') AS valor_parcelas_pendente
        FROM contratos c
        JOIN clientes cl ON cl.id = c.cliente_id
        ORDER BY c.criado_em DESC
    ");
    $contratos = $stmt->fetchAll();

    echo json_encode(['sucesso' => true, 'contratos' => $contratos], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao listar contratos', 'detalhe' => $e->getMessage()]);
}
