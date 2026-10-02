<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Prévia do fechamento de consultores de uma competência, em JSON — só
 * leitura, não grava nada. Usado pela tela
 * crm-newsiga-fechamento-consultores.php. A regra de cálculo está em
 * calculo-fechamento-consultores.php.
 *
 * Uso: previa-fechamento-consultores.php?competencia=2026-09
 */

require_once __DIR__ . '/calculo-fechamento-consultores.php';

header('Content-Type: application/json; charset=utf-8');

$competencia = trim($_GET['competencia'] ?? '');
if (!preg_match('/^\d{4}-\d{2}$/', $competencia)) {
    http_response_code(422);
    echo json_encode(['erro' => 'Competência inválida (formato esperado AAAA-MM).']);
    exit;
}

// A consulta ao Movidesk é paginada, com pausa entre as páginas por
// causa do limite de requisições dele — passa fácil dos 30s padrão.
set_time_limit(0);

try {
    $previa = calcular_previa_fechamento_consultores(getDb(), $competencia);
    echo json_encode(['sucesso' => true] + $previa, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao calcular a prévia do fechamento de consultores', 'detalhe' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
