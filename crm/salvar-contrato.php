<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Recebe o POST do formulário "Novo contrato" (crm-newsiga-cadastro-contrato.html)
 * e grava em `contratos` — e em `parcelas`, se o tipo for projeto_parcelado —
 * numa única transação. Sempre nasce com faturamento_gerenciado_por = 'sistema';
 * a checagem de assinatura ASAAS já existente acontece depois, na tela de
 * confirmação (prepararConfirmacaoAsaas), quando o contrato for aprovado.
 */

require_once __DIR__ . '/db.php';

// ---------------------------------------------------------
// Funções auxiliares — divisão automática de parcelas
// ---------------------------------------------------------

/** Data da Páscoa (algoritmo de Meeus/Jones/Butcher — sem depender da extensão calendar do PHP) */
function calcularPascoa(int $ano): string
{
    $a = $ano % 19;
    $b = intdiv($ano, 100);
    $c = $ano % 100;
    $d = intdiv($b, 4);
    $e = $b % 4;
    $f = intdiv($b + 8, 25);
    $g = intdiv($b - $f + 1, 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = intdiv($c, 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = intdiv($a + 11 * $h + 22 * $l, 451);
    $mes = intdiv($h + $l - 7 * $m + 114, 31);
    $dia = (($h + $l - 7 * $m + 114) % 31) + 1;
    return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
}

/** Feriados nacionais fixos + móveis (baseados na Páscoa). Não inclui feriados municipais/estaduais. */
function feriadosNacionais(int $ano): array
{
    $fixos = [
        "$ano-01-01", "$ano-04-21", "$ano-05-01",
        "$ano-09-07", "$ano-10-12", "$ano-11-02",
        "$ano-11-15", "$ano-12-25",
    ];
    $pascoaTs = strtotime(calcularPascoa($ano));
    $moveis = [
        date('Y-m-d', strtotime('-47 days', $pascoaTs)), // Carnaval (terça)
        date('Y-m-d', strtotime('-2 days', $pascoaTs)),  // Sexta-feira Santa
        date('Y-m-d', strtotime('+60 days', $pascoaTs)), // Corpus Christi
    ];
    return array_merge($fixos, $moveis);
}

/** Empurra a data pro próximo dia útil, se cair em fim de semana ou feriado nacional */
function proximoDiaUtil(string $data): string
{
    $ts = strtotime($data);
    $feriadosCache = [];
    while (true) {
        $ano = (int) date('Y', $ts);
        if (!isset($feriadosCache[$ano])) {
            $feriadosCache[$ano] = feriadosNacionais($ano);
        }
        $diaSemana = (int) date('w', $ts); // 0 = domingo, 6 = sábado
        $dataStr = date('Y-m-d', $ts);
        if ($diaSemana !== 0 && $diaSemana !== 6 && !in_array($dataStr, $feriadosCache[$ano], true)) {
            return $dataStr;
        }
        $ts = strtotime('+1 day', $ts);
    }
}

/** Divide o valor total em N parcelas — todas iguais, exceto a última, que absorve a diferença de arredondamento (mesmo padrão do ASAAS) */
function dividirValorEmParcelas(float $valorTotal, int $numParcelas): array
{
    $totalCentavos = (int) round($valorTotal * 100);
    $baseCentavos  = intdiv($totalCentavos, $numParcelas);
    $valores       = array_fill(0, $numParcelas, $baseCentavos);
    $diferenca     = $totalCentavos - ($baseCentavos * $numParcelas);
    $valores[$numParcelas - 1] += $diferenca;
    return array_map(fn($centavos) => $centavos / 100, $valores);
}

/** Gera as datas de vencimento mensais a partir da primeira, prorrogando pra dia útil */
function gerarDatasParcelas(string $primeiraData, int $numParcelas): array
{
    $diaOriginal = (int) date('d', strtotime($primeiraData));
    $anoBase     = (int) date('Y', strtotime($primeiraData));
    $mesBase     = (int) date('m', strtotime($primeiraData));

    $datas = [];
    for ($i = 0; $i < $numParcelas; $i++) {
        $mesAlvoTotal = $mesBase - 1 + $i;
        $anoAlvo = $anoBase + intdiv($mesAlvoTotal, 12);
        $mesAlvo = ($mesAlvoTotal % 12) + 1;
        $ultimoDiaDoMes = (int) date('t', mktime(0, 0, 0, $mesAlvo, 1, $anoAlvo));
        $diaAjustado = min($diaOriginal, $ultimoDiaDoMes); // protege datas tipo dia 31 caindo num mês de 30

        $dataBase = sprintf('%04d-%02d-%02d', $anoAlvo, $mesAlvo, $diaAjustado);
        $datas[] = proximoDiaUtil($dataBase);
    }
    return $datas;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

// ---------------------------------------------------------
// Leitura e validação básica
// ---------------------------------------------------------
$clienteId       = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
$tipo            = $_POST['tipo'] ?? '';
$descricao       = trim($_POST['descricao'] ?? '');
$descricaoServico = trim($_POST['descricao_servico'] ?? '') ?: null;
$origemProposta  = trim($_POST['origem_proposta'] ?? '') ?: null;
$movideskContractName = trim($_POST['movidesk_contract_name'] ?? '') ?: null;
$status          = $_POST['status'] ?? 'rascunho';
$faturamentoManual = !empty($_POST['faturamento_manual']);
$faturamentoGerenciadoPor = $faturamentoManual ? 'manual' : 'sistema';

$tiposValidos   = ['mensalidade_fixa', 'hora_aberta', 'banco_horas_minimo', 'banco_horas_consumo', 'projeto_parcelado'];
$statusValidos  = ['rascunho', 'aprovado'];

$erros = [];
if (!$clienteId) $erros[] = 'Cliente inválido.';
if (!in_array($tipo, $tiposValidos, true)) $erros[] = 'Tipo de contrato inválido.';
if ($descricao === '') $erros[] = 'Descrição é obrigatória.';
if (!in_array($status, $statusValidos, true)) $erros[] = 'Status inicial inválido.';

// Confere se o cliente existe ANTES de tentar gravar — evita um erro
// genérico de foreign key lá na frente, quando dá pra avisar direito aqui.
if ($clienteId) {
    $check = $db->prepare('SELECT id FROM clientes WHERE id = ?');
    $check->execute([$clienteId]);
    if (!$check->fetch()) {
        $erros[] = 'Cliente não encontrado.';
    }
}

// ---------------------------------------------------------
// Campos condicionais — variam conforme o tipo de contrato
// ---------------------------------------------------------
$valor              = null;
$valorHora          = null;
$valorHoraExcedente = null;
$horasBanco         = null;
$horasMinimas       = null;
$diaVencimento      = null;
$parcelas           = [];

switch ($tipo) {
    case 'mensalidade_fixa':
        $valor         = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
        $diaVencimento = filter_input(INPUT_POST, 'dia_vencimento', FILTER_VALIDATE_INT);
        if (!$valor || $valor <= 0) $erros[] = 'Valor mensal inválido.';
        if (!$diaVencimento || $diaVencimento < 1 || $diaVencimento > 31) $erros[] = 'Dia de vencimento inválido.';
        break;

    case 'hora_aberta':
        // Cenário 3: sem banco, sem excedente — só consumo × taxa única.
        $valorHora     = filter_input(INPUT_POST, 'valor_hora', FILTER_VALIDATE_FLOAT);
        $diaVencimento = filter_input(INPUT_POST, 'dia_vencimento', FILTER_VALIDATE_INT);
        if (!$valorHora || $valorHora <= 0) $erros[] = 'Valor por hora inválido.';
        if (!$diaVencimento || $diaVencimento < 1 || $diaVencimento > 31) $erros[] = 'Dia de vencimento inválido.';
        break;

    case 'banco_horas_minimo':
        // Cenário 1: valor mínimo garantido faturado sempre, mais excedente
        // (opcional) se o consumo passar da quantidade de horas do pacote.
        $valor         = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);      // mínimo garantido
        $valorHora     = filter_input(INPUT_POST, 'valor_hora', FILTER_VALIDATE_FLOAT);  // hora excedente — opcional
        $horasMinimas  = filter_input(INPUT_POST, 'horas_minimas', FILTER_VALIDATE_FLOAT);
        $diaVencimento = filter_input(INPUT_POST, 'dia_vencimento', FILTER_VALIDATE_INT);
        if (!$valor || $valor <= 0) $erros[] = 'Valor mínimo garantido inválido.';
        // Sem excedente definido (0, vazio ou não numérico) = contrato sem cobrança
        // além do mínimo garantido, mesmo que o consumo ultrapasse o previsto.
        if ($valorHora === false || $valorHora < 0) $erros[] = 'Valor por hora excedente inválido.';
        if (!$valorHora) {
            $valorHora = null;
            $horasMinimas = null; // sem excedente, a quantidade de horas do pacote não é usada em nada
        } elseif (!$horasMinimas || $horasMinimas <= 0) {
            // Tem excedente configurado — precisa saber a partir de quantas
            // horas ele começa, senão o fechamento não consegue calcular certo.
            $erros[] = 'Quantidade de horas do pacote é obrigatória quando há valor de excedente.';
        }
        if (!$diaVencimento || $diaVencimento < 1 || $diaVencimento > 31) $erros[] = 'Dia de vencimento inválido.';
        break;

    case 'banco_horas_consumo':
        // Cenário 2: sem mínimo — fatura só o que foi consumido dentro do
        // banco (consumo × valor_hora), com uma taxa diferenciada para o
        // que passar do tamanho do banco.
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
        $valorTotal      = filter_input(INPUT_POST, 'parcela_valor_total', FILTER_VALIDATE_FLOAT);
        $numParcelas     = filter_input(INPUT_POST, 'parcela_qtd', FILTER_VALIDATE_INT);
        $primeiraData    = $_POST['parcela_primeira_data'] ?? '';

        if (!$valorTotal || $valorTotal <= 0) $erros[] = 'Valor total do projeto inválido.';
        if (!$numParcelas || $numParcelas < 1 || $numParcelas > 60) $erros[] = 'Quantidade de parcelas inválida.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $primeiraData)) $erros[] = 'Data da 1ª parcela inválida.';

        if (!$erros) {
            $valoresCalculados = dividirValorEmParcelas($valorTotal, $numParcelas);
            $datasCalculadas   = gerarDatasParcelas($primeiraData, $numParcelas);
            foreach ($valoresCalculados as $i => $valorP) {
                $parcelas[] = ['numero' => $i + 1, 'valor' => $valorP, 'vencimento' => $datasCalculadas[$i]];
            }
        }
        break;
}

if ($erros) {
    http_response_code(422);
    echo json_encode(['erro' => 'Dados inválidos', 'detalhes' => $erros]);
    exit;
}

// ---------------------------------------------------------
// Gravação — contrato + parcelas (se houver) na mesma transação,
// pra nunca ficar um contrato órfão sem suas parcelas por causa
// de uma falha no meio do caminho.
// ---------------------------------------------------------
try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO contratos
            (cliente_id, tipo, descricao, descricao_servico, valor, valor_hora, valor_hora_excedente, horas_banco, horas_minimas, movidesk_contract_name, dia_vencimento, origem_proposta, status, faturamento_gerenciado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$clienteId, $tipo, $descricao, $descricaoServico, $valor, $valorHora, $valorHoraExcedente, $horasBanco, $horasMinimas, $movideskContractName, $diaVencimento, $origemProposta, $status, $faturamentoGerenciadoPor]);
    $contratoId = (int) $db->lastInsertId();

    if ($tipo === 'projeto_parcelado') {
        $stmtParcela = $db->prepare("
            INSERT INTO parcelas (contrato_id, numero, valor, vencimento, status)
            VALUES (?, ?, ?, ?, 'a_gerar')
        ");
        foreach ($parcelas as $p) {
            $stmtParcela->execute([$contratoId, $p['numero'], $p['valor'], $p['vencimento']]);
        }
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao gravar contrato', 'detalhe' => $e->getMessage()]);
    exit;
}

// Se o contrato já nasceu 'aprovado' E é faturado pelo sistema (ASAAS),
// o front-end deve levar o usuário direto pra tela de confirmação —
// contrato de faturamento manual nunca passa por lá, não tem cliente
// ASAAS nenhum pra confirmar.
echo json_encode([
    'sucesso' => true,
    'contrato_id' => $contratoId,
    'redirecionar_para_confirmacao_asaas' => $status === 'aprovado' && !$faturamentoManual,
]);
