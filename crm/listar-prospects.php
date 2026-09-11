<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Lista prospects — usado na tela de listagem e no painel (pipeline
 * comercial). Prioritários primeiro, depois por data de atualização
 * mais recente (é o que reflete negociação "quente").
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDb();
    $stmt = $db->query('SELECT * FROM prospects ORDER BY prioritario DESC, atualizado_em DESC');
    echo json_encode(['sucesso' => true, 'prospects' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao listar prospects', 'detalhe' => $e->getMessage()]);
}
