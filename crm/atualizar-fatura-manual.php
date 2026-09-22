<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Atualiza um lançamento manual de receita já existente (corrigir
 * competência/valor/vencimento sem precisar excluir e lançar de novo).
 * Não mexe em `status` (isso é só por atualizar-status-fatura.php) nem
 * em `contrato_id`. Só funciona pra faturas de contrato com
 * faturamento_gerenciado_por = 'manual' — mesma regra de
 * atualizar-despesa-competencia.php.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$id          = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$competencia = trim($_POST['competencia'] ?? '');
$valor       = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
$vencimento  = trim($_POST['vencimento'] ?? '');

$erros = [];
if (!$id) $erros[] = 'Fatura inválida.';
if (!preg_match('/^\d{4}-\d{2}$/', $competencia)) $erros[] = 'Competência inválida (formato esperado AAAA-MM).';
if (!$valor || $valor <= 0) $erros[] = 'Valor inválido.';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimento)) $erros[] = 'Vencimento inválido.';

$fatura = null;
if ($id) {
    $check = $db->prepare("
        SELECT f.id, f.contrato_id, c.faturamento_gerenciado_por
        FROM faturas f JOIN contratos c ON c.id = f.contrato_id
        WHERE f.id = ?
    ");
    $check->execute([$id]);
    $fatura = $check->fetch();
    if (!$fatura) {
        $erros[] = 'Fatura não encontrada.';
    } elseif ($fatura['faturamento_gerenciado_por'] !== 'manual') {
        $erros[] = 'Só é possível editar faturas de contrato com faturamento manual.';
    }
}

if ($erros) {
    http_response_code(422);
    echo json_encode(['erro' => 'Dados inválidos', 'detalhes' => $erros]);
    exit;
}

$checkDuplicado = $db->prepare('SELECT id FROM faturas WHERE contrato_id = ? AND competencia = ? AND id != ?');
$checkDuplicado->execute([$fatura['contrato_id'], $competencia, $id]);
if ($checkDuplicado->fetch()) {
    http_response_code(422);
    echo json_encode(['erro' => "Já existe outro lançamento para a competência $competencia neste contrato."]);
    exit;
}

try {
    $stmt = $db->prepare("
        UPDATE faturas
        SET competencia = ?, valor = ?, valor_liquido = ?, vencimento = ?
        WHERE id = ?
    ");
    $stmt->execute([$competencia, $valor, $valor, $vencimento, $id]);

    echo json_encode(['sucesso' => true, 'fatura_id' => $id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao atualizar fatura', 'detalhe' => $e->getMessage()]);
}
