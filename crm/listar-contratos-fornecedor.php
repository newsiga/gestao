<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Lista contratos de fornecedor com o nome do fornecedor, em JSON —
 * mesmo padrão de listar-contratos.php.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDb();

    $stmt = $db->query("
        SELECT
            cf.id, cf.fornecedor_id, cf.tipo, cf.descricao, cf.valor, cf.valor_hora, cf.valor_hora_excedente,
            cf.horas_banco, cf.horas_minimas, cf.dia_vencimento, cf.status, cf.criado_em,
            f.nome AS fornecedor_nome, f.tipo AS fornecedor_tipo, f.movidesk_technician_name
        FROM contratos_fornecedor cf
        JOIN fornecedores f ON f.id = cf.fornecedor_id
        ORDER BY cf.criado_em DESC
    ");
    echo json_encode(['sucesso' => true, 'contratos' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao listar contratos de fornecedor', 'detalhe' => $e->getMessage()]);
}
