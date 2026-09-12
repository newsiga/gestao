<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Atualiza um lançamento de despesa de competência já existente (pra
 * corrigir competência/valor/vencimento/horas errados sem precisar
 * excluir e lançar de novo). Não mexe em `status` — isso continua só
 * por atualizar-status-despesa.php, que valida as transições — nem em
 * `contrato_fornecedor_id`, pra não trocar de fornecedor/contrato no
 * meio do caminho (se for o caso, exclua e lance de novo no contrato certo).
 *
 * Só permite editar lançamentos com origem = 'manual', mesma regra de
 * excluir-despesa-competencia.php.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$id              = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$competencia     = trim($_POST['competencia'] ?? '');
$horasConsumidas = filter_input(INPUT_POST, 'horas_consumidas', FILTER_VALIDATE_FLOAT) ?: null;
$valor           = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
$vencimento      = trim($_POST['vencimento'] ?? '');

$erros = [];
if (!$id) $erros[] = 'Despesa inválida.';
if (!preg_match('/^\d{4}-\d{2}$/', $competencia)) $erros[] = 'Competência inválida (formato esperado AAAA-MM).';
if (!$valor || $valor <= 0) $erros[] = 'Valor inválido.';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimento)) $erros[] = 'Vencimento inválido.';

$despesa = null;
if ($id) {
    $check = $db->prepare('SELECT id, origem, contrato_fornecedor_id FROM despesas_competencia WHERE id = ?');
    $check->execute([$id]);
    $despesa = $check->fetch();
    if (!$despesa) {
        $erros[] = 'Despesa não encontrada.';
    } elseif ($despesa['origem'] !== 'manual') {
        $erros[] = 'Só é possível editar lançamentos manuais.';
    }
}

if ($erros) {
    http_response_code(422);
    echo json_encode(['erro' => 'Dados inválidos', 'detalhes' => $erros]);
    exit;
}

// Evita duplicar competência com outro lançamento do mesmo contrato
// (mesma regra de salvar-despesa-competencia.php), só que excluindo a
// própria despesa da checagem.
$checkDuplicado = $db->prepare('SELECT id FROM despesas_competencia WHERE contrato_fornecedor_id = ? AND competencia = ? AND id != ?');
$checkDuplicado->execute([$despesa['contrato_fornecedor_id'], $competencia, $id]);
if ($checkDuplicado->fetch()) {
    http_response_code(422);
    echo json_encode(['erro' => "Já existe outro lançamento para a competência $competencia neste contrato."]);
    exit;
}

try {
    $stmt = $db->prepare("
        UPDATE despesas_competencia
        SET competencia = ?, horas_consumidas = ?, valor = ?, vencimento = ?
        WHERE id = ?
    ");
    $stmt->execute([$competencia, $horasConsumidas, $valor, $vencimento, $id]);

    echo json_encode(['sucesso' => true, 'despesa_id' => $id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao atualizar despesa', 'detalhe' => $e->getMessage()]);
}
