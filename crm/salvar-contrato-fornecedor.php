<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Recebe o POST do formulário "Novo contrato de fornecedor" e grava em
 * `contratos_fornecedor`. Espelha salvar-contrato.php (lado de receita),
 * invertendo o sentido do fluxo (pagar em vez de cobrar) — mesmos 4
 * tipos recorrentes (mensalidade_fixa, hora_aberta, banco_horas_minimo,
 * banco_horas_consumo); "projeto_parcelado" não se aplica aqui, um
 * projeto pontual de fornecedor entra como um único lançamento manual
 * em despesas_competencia, sem precisar de todo o aparato de parcelas.
 * Nasce sempre em 'rascunho' — sem gatilho de ASAAS, então sem o status
 * intermediário 'aprovado' que o lado de receita usa.
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

$tiposValidos = ['mensalidade_fixa', 'hora_aberta', 'banco_horas_minimo', 'banco_horas_consumo'];

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

// ---------------------------------------------------------
// Campos condicionais — mesma lógica de salvar-contrato.php
// ---------------------------------------------------------
$valor              = null;
$valorHora          = null;
$valorHoraExcedente = null;
$horasBanco         = null;
$horasMinimas       = null;
$diaVencimento      = filter_input(INPUT_POST, 'dia_vencimento', FILTER_VALIDATE_INT);

if (!$diaVencimento || $diaVencimento < 1 || $diaVencimento > 31) {
    $erros[] = 'Dia de vencimento inválido.';
}

switch ($tipo) {
    case 'mensalidade_fixa':
        $valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
        if (!$valor || $valor <= 0) $erros[] = 'Valor mensal inválido.';
        break;

    case 'hora_aberta':
        $valorHora = filter_input(INPUT_POST, 'valor_hora', FILTER_VALIDATE_FLOAT);
        if (!$valorHora || $valorHora <= 0) $erros[] = 'Valor por hora inválido.';
        break;

    case 'banco_horas_minimo':
        $valor        = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);     // mínimo garantido
        $valorHora    = filter_input(INPUT_POST, 'valor_hora', FILTER_VALIDATE_FLOAT); // hora excedente — opcional
        $horasMinimas = filter_input(INPUT_POST, 'horas_minimas', FILTER_VALIDATE_FLOAT);
        if (!$valor || $valor <= 0) $erros[] = 'Valor mínimo garantido inválido.';
        if ($valorHora === false || $valorHora < 0) $erros[] = 'Valor por hora excedente inválido.';
        if (!$valorHora) {
            $valorHora = null;
            $horasMinimas = null;
        } elseif (!$horasMinimas || $horasMinimas <= 0) {
            $erros[] = 'Quantidade de horas do pacote é obrigatória quando há valor de excedente.';
        }
        break;

    case 'banco_horas_consumo':
        $horasBanco         = filter_input(INPUT_POST, 'horas_banco', FILTER_VALIDATE_FLOAT);
        $valorHora          = filter_input(INPUT_POST, 'valor_hora', FILTER_VALIDATE_FLOAT);
        $valorHoraExcedente = filter_input(INPUT_POST, 'valor_hora_excedente', FILTER_VALIDATE_FLOAT);
        if (!$horasBanco || $horasBanco <= 0) $erros[] = 'Tamanho do banco de horas inválido.';
        if (!$valorHora || $valorHora <= 0) $erros[] = 'Valor por hora (dentro do banco) inválido.';
        if (!$valorHoraExcedente || $valorHoraExcedente <= 0) $erros[] = 'Valor por hora excedente inválido.';
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
            (fornecedor_id, tipo, valor, valor_hora, valor_hora_excedente, horas_banco, horas_minimas, dia_vencimento, descricao, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'rascunho')
    ");
    $stmt->execute([$fornecedorId, $tipo, $valor, $valorHora, $valorHoraExcedente, $horasBanco, $horasMinimas, $diaVencimento, $descricao]);
    $contratoId = (int) $db->lastInsertId();

    echo json_encode(['sucesso' => true, 'contrato_fornecedor_id' => $contratoId]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao gravar contrato de fornecedor', 'detalhe' => $e->getMessage()]);
}
