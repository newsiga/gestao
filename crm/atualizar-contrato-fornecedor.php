<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Atualiza um contrato de fornecedor existente (valores, descrição,
 * vínculo opcional com cliente). Mesma validação de
 * salvar-contrato-fornecedor.php — só muda pra UPDATE em vez de INSERT.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$id            = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$tipo          = $_POST['tipo'] ?? '';
$descricao     = trim($_POST['descricao'] ?? '');
$clienteId     = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT) ?: null;

$tiposValidos = ['mensalidade_fixa', 'hora_aberta'];

$erros = [];
if (!$id) $erros[] = 'Contrato inválido.';
if (!in_array($tipo, $tiposValidos, true)) $erros[] = 'Tipo de contrato inválido.';
if ($descricao === '') $erros[] = 'Descrição é obrigatória.';

if ($id) {
    $check = $db->prepare('SELECT id FROM contratos_fornecedor WHERE id = ?');
    $check->execute([$id]);
    if (!$check->fetch()) {
        $erros[] = 'Contrato de fornecedor não encontrado.';
    }
}

if ($clienteId) {
    $checkCliente = $db->prepare('SELECT id FROM clientes WHERE id = ?');
    $checkCliente->execute([$clienteId]);
    if (!$checkCliente->fetch()) {
        $erros[] = 'Cliente selecionado não encontrado.';
    }
}

// ---------------------------------------------------------
// Campos condicionais
// ---------------------------------------------------------
$valor         = null;
$valorHora     = null;
$diaVencimento = filter_input(INPUT_POST, 'dia_vencimento', FILTER_VALIDATE_INT);

if (!$diaVencimento || $diaVencimento < 1 || $diaVencimento > 31) {
    $erros[] = 'Dia de vencimento inválido.';
}

switch ($tipo) {
    case 'mensalidade_fixa':
        $valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
        if (!$valor || $valor <= 0) $erros[] = 'Valor mensal inválido.';
        $clienteId = null; // não faz sentido vincular cliente a um valor fixo
        break;

    case 'hora_aberta':
        $valorHora = filter_input(INPUT_POST, 'valor_hora', FILTER_VALIDATE_FLOAT);
        if (!$valorHora || $valorHora <= 0) $erros[] = 'Valor por hora inválido.';
        break;
}

if ($erros) {
    http_response_code(422);
    echo json_encode(['erro' => 'Dados inválidos', 'detalhes' => $erros]);
    exit;
}

try {
    $stmt = $db->prepare("
        UPDATE contratos_fornecedor
        SET tipo = ?, valor = ?, valor_hora = ?, dia_vencimento = ?, descricao = ?, cliente_id = ?
        WHERE id = ?
    ");
    $stmt->execute([$tipo, $valor, $valorHora, $diaVencimento, $descricao, $clienteId, $id]);

    echo json_encode(['sucesso' => true, 'contrato_fornecedor_id' => $id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao atualizar contrato de fornecedor', 'detalhe' => $e->getMessage()]);
}
