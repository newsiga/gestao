<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Atualiza um contrato existente. O tipo e o cliente NÃO são editáveis
 * aqui de propósito — trocar o tipo de um contrato já em andamento é uma
 * mudança grande demais pra caber num simples "salvar edição" (implicaria
 * recalcular parcelas/faturas já geradas). Se precisar mudar o tipo, o
 * caminho é encerrar esse contrato e criar um novo.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$contratoId      = filter_input(INPUT_POST, 'contrato_id', FILTER_VALIDATE_INT);
$descricao       = trim($_POST['descricao'] ?? '');
$descricaoServico = trim($_POST['descricao_servico'] ?? '') ?: null;
$origemProposta  = trim($_POST['origem_proposta'] ?? '') ?: null;
$movideskContractName = trim($_POST['movidesk_contract_name'] ?? '') ?: null;

if (!$contratoId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Contrato inválido.']);
    exit;
}

$stmt = $db->prepare('SELECT tipo FROM contratos WHERE id = ?');
$stmt->execute([$contratoId]);
$atual = $stmt->fetch();
if (!$atual) {
    http_response_code(404);
    echo json_encode(['erro' => 'Contrato não encontrado.']);
    exit;
}
$tipo = $atual['tipo'];

$erros = [];
if ($descricao === '') $erros[] = 'Descrição é obrigatória.';

// ---------------------------------------------------------
// Campos condicionais — mesma lógica de validação do cadastro,
// só que aqui SEM mexer no tipo.
// ---------------------------------------------------------
$valor              = null;
$valorHora          = null;
$valorHoraExcedente = null;
$horasBanco         = null;
$horasMinimas       = null;
$diaVencimento      = null;

switch ($tipo) {
    case 'mensalidade_fixa':
        $valor         = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
        $diaVencimento = filter_input(INPUT_POST, 'dia_vencimento', FILTER_VALIDATE_INT);
        if (!$valor || $valor <= 0) $erros[] = 'Valor mensal inválido.';
        if (!$diaVencimento || $diaVencimento < 1 || $diaVencimento > 31) $erros[] = 'Dia de vencimento inválido.';
        break;

    case 'hora_aberta':
        $valorHora     = filter_input(INPUT_POST, 'valor_hora', FILTER_VALIDATE_FLOAT);
        $diaVencimento = filter_input(INPUT_POST, 'dia_vencimento', FILTER_VALIDATE_INT);
        if (!$valorHora || $valorHora <= 0) $erros[] = 'Valor por hora inválido.';
        if (!$diaVencimento || $diaVencimento < 1 || $diaVencimento > 31) $erros[] = 'Dia de vencimento inválido.';
        break;

    case 'banco_horas_minimo':
        $valor         = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
        $valorHora     = filter_input(INPUT_POST, 'valor_hora', FILTER_VALIDATE_FLOAT);
        $horasMinimas  = filter_input(INPUT_POST, 'horas_minimas', FILTER_VALIDATE_FLOAT);
        $diaVencimento = filter_input(INPUT_POST, 'dia_vencimento', FILTER_VALIDATE_INT);
        if (!$valor || $valor <= 0) $erros[] = 'Valor mínimo garantido inválido.';
        if ($valorHora === false || $valorHora < 0) $erros[] = 'Valor por hora excedente inválido.';
        if (!$valorHora) {
            $valorHora = null;
            $horasMinimas = null;
        } elseif (!$horasMinimas || $horasMinimas <= 0) {
            $erros[] = 'Quantidade de horas do pacote é obrigatória quando há valor de excedente.';
        }
        if (!$diaVencimento || $diaVencimento < 1 || $diaVencimento > 31) $erros[] = 'Dia de vencimento inválido.';
        break;

    case 'banco_horas_consumo':
        $horasBanco         = filter_input(INPUT_POST, 'horas_banco', FILTER_VALIDATE_FLOAT);
        $valorHora          = filter_input(INPUT_POST, 'valor_hora', FILTER_VALIDATE_FLOAT);
        $valorHoraExcedente = filter_input(INPUT_POST, 'valor_hora_excedente', FILTER_VALIDATE_FLOAT);
        $diaVencimento      = filter_input(INPUT_POST, 'dia_vencimento', FILTER_VALIDATE_INT);
        if (!$horasBanco || $horasBanco <= 0) $erros[] = 'Tamanho do banco de horas inválido.';
        if (!$valorHora || $valorHora <= 0) $erros[] = 'Valor por hora (dentro do banco) inválido.';
        if (!$valorHoraExcedente || $valorHoraExcedente <= 0) $erros[] = 'Valor por hora excedente inválido.';
        if (!$diaVencimento || $diaVencimento < 1 || $diaVencimento > 31) $erros[] = 'Dia de vencimento inválido.';
        break;

    case 'projeto_parcelado':
        // Parcelas individuais têm tela própria — aqui só descrição/origem
        // (e agora descrição do serviço) são editáveis pra esse tipo.
        break;
}

if ($erros) {
    http_response_code(422);
    echo json_encode(['erro' => 'Dados inválidos', 'detalhes' => $erros]);
    exit;
}

try {
    $stmt = $db->prepare("
        UPDATE contratos
        SET descricao = ?, descricao_servico = ?, origem_proposta = ?, valor = ?, valor_hora = ?,
            valor_hora_excedente = ?, horas_banco = ?, horas_minimas = ?, movidesk_contract_name = ?, dia_vencimento = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $descricao, $descricaoServico, $origemProposta, $valor, $valorHora,
        $valorHoraExcedente, $horasBanco, $horasMinimas, $movideskContractName, $diaVencimento, $contratoId,
    ]);

    echo json_encode(['sucesso' => true, 'contrato_id' => $contratoId]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao atualizar contrato', 'detalhe' => $e->getMessage()]);
}
