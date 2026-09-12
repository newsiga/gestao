<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Recebe o POST do formulário "Novo contrato de fornecedor" e grava em
 * `contratos_fornecedor`. Diferente do lado de receita, fornecedor não
 * tem banco de horas — só dois tipos: mensalidade_fixa (fixo mensal,
 * com ou sem despesa variável lançada mês a mês) e hora_aberta (consumo
 * × taxa, uma taxa por relação/cliente — ex: Robson tem um contrato
 * hora_aberta por cliente/parceiro que atende, cada um com sua própria
 * taxa). "projeto_parcelado" também não se aplica aqui; um projeto
 * pontual de fornecedor entra como um único lançamento manual em
 * despesas_competencia, sem precisar de todo o aparato de parcelas.
 * Nasce sempre em 'rascunho' — sem gatilho de ASAAS, então sem o status
 * intermediário 'aprovado' que o lado de receita usa.
 *
 * `cliente_id` (opcional, só faz sentido em hora_aberta): um fornecedor
 * cobra a mesma taxa pra qualquer cliente na maioria dos casos — nesse
 * caso, um único contrato com cliente_id NULL cobre todo mundo ("regra
 * geral"). Quando um fornecedor tem uma taxa diferente pra um cliente
 * específico (ex: Robson recebe R$50/h da Tron, R$70/h dos demais),
 * cadastra-se um contrato adicional com cliente_id preenchido — vale só
 * pra aquele cliente, sem precisar recriar um contrato por cliente pra
 * todo mundo.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$fornecedorId  = filter_input(INPUT_POST, 'fornecedor_id', FILTER_VALIDATE_INT);
$tipo          = $_POST['tipo'] ?? '';
$descricao     = trim($_POST['descricao'] ?? '');
$clienteId     = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT) ?: null;

$tiposValidos = ['mensalidade_fixa', 'hora_aberta'];

$erros = [];
if (!$fornecedorId) $erros[] = 'Fornecedor inválido.';
if (!in_array($tipo, $tiposValidos, true)) $erros[] = 'Tipo de contrato inválido.';
if ($descricao === '') $erros[] = 'Descrição é obrigatória.';

if ($fornecedorId) {
    $check = $db->prepare('SELECT id FROM fornecedores WHERE id = ?');
    $check->execute([$fornecedorId]);
    if (!$check->fetch()) {
        $erros[] = 'Fornecedor não encontrado.';
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
        INSERT INTO contratos_fornecedor
            (fornecedor_id, cliente_id, tipo, valor, valor_hora, dia_vencimento, descricao, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'rascunho')
    ");
    $stmt->execute([$fornecedorId, $clienteId, $tipo, $valor, $valorHora, $diaVencimento, $descricao]);
    $contratoId = (int) $db->lastInsertId();

    echo json_encode(['sucesso' => true, 'contrato_fornecedor_id' => $contratoId]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao gravar contrato de fornecedor', 'detalhe' => $e->getMessage()]);
}
