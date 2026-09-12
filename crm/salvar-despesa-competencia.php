<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Lançamento MANUAL de uma despesa de competência (Fase 1 — sem cálculo
 * automático via Movidesk ainda, ver docs/despesas-fornecedores-crm.md).
 * Grava uma linha em `despesas_competencia` com origem = 'manual'.
 *
 * Só aceita um lançamento por (contrato_fornecedor_id, competencia) —
 * se já existir, aponta pra edição/exclusão do lançamento existente em
 * vez de duplicar (diferente do fechar-competencia.php do lado de
 * receita, que reaproveita a fatura travada; aqui, sem processamento em
 * lote, é mais simples e mais seguro travar de vez).
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$contratoFornecedorId = filter_input(INPUT_POST, 'contrato_fornecedor_id', FILTER_VALIDATE_INT);
$competencia          = trim($_POST['competencia'] ?? '');
$horasConsumidas      = filter_input(INPUT_POST, 'horas_consumidas', FILTER_VALIDATE_FLOAT) ?: null;
$valor                = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
$vencimento           = trim($_POST['vencimento'] ?? '');

$erros = [];
if (!$contratoFornecedorId) $erros[] = 'Contrato de fornecedor inválido.';
if (!preg_match('/^\d{4}-\d{2}$/', $competencia)) $erros[] = 'Competência inválida (formato esperado AAAA-MM).';
if (!$valor || $valor <= 0) $erros[] = 'Valor inválido.';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimento)) $erros[] = 'Vencimento inválido.';

if ($contratoFornecedorId) {
    $check = $db->prepare('SELECT id FROM contratos_fornecedor WHERE id = ?');
    $check->execute([$contratoFornecedorId]);
    if (!$check->fetch()) {
        $erros[] = 'Contrato de fornecedor não encontrado.';
    }
}

if ($erros) {
    http_response_code(422);
    echo json_encode(['erro' => 'Dados inválidos', 'detalhes' => $erros]);
    exit;
}

$checkExistente = $db->prepare('SELECT id FROM despesas_competencia WHERE contrato_fornecedor_id = ? AND competencia = ?');
$checkExistente->execute([$contratoFornecedorId, $competencia]);
if ($checkExistente->fetch()) {
    http_response_code(422);
    echo json_encode(['erro' => "Já existe um lançamento para a competência $competencia neste contrato. Edite ou exclua o existente em vez de lançar de novo."]);
    exit;
}

try {
    $stmt = $db->prepare("
        INSERT INTO despesas_competencia
            (contrato_fornecedor_id, competencia, horas_consumidas, valor, vencimento, status, origem)
        VALUES (?, ?, ?, ?, ?, 'a_pagar', 'manual')
    ");
    $stmt->execute([$contratoFornecedorId, $competencia, $horasConsumidas, $valor, $vencimento]);
    $despesaId = (int) $db->lastInsertId();

    echo json_encode(['sucesso' => true, 'despesa_id' => $despesaId]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao gravar despesa de competência', 'detalhe' => $e->getMessage()]);
}
