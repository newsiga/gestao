<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Lança em lote as linhas que o Felipe revisou e marcou na tela de
 * fechamento de consultores (crm-newsiga-fechamento-consultores.php).
 * Cada linha vira um registro em `despesas_competencia`, status
 * 'a_pagar' — mesmo destino do lançamento manual
 * (salvar-despesa-competencia.php), só que vários de uma vez.
 *
 * O valor gravado é o que veio da tela (valor calculado, que o Felipe
 * pode ter ajustado, + adicional) — o cálculo NÃO é refeito aqui: a
 * prévia é só uma sugestão, quem decide o valor é quem revisou.
 *
 * Tudo ou nada: se uma linha for inválida ou já tiver lançamento nessa
 * competência, nenhuma é gravada.
 *
 * POST: competencia=AAAA-MM, itens=JSON de
 *   [{contrato_fornecedor_id, horas, valor_base, valor_adicional, observacao, vencimento, calculo}]
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$competencia = trim($_POST['competencia'] ?? '');
$itens = json_decode($_POST['itens'] ?? '', true);

$erros = [];
if (!preg_match('/^\d{4}-\d{2}$/', $competencia)) $erros[] = 'Competência inválida (formato esperado AAAA-MM).';
if (!is_array($itens) || count($itens) === 0) $erros[] = 'Nenhuma linha selecionada.';

$checkContrato = $db->prepare('SELECT cf.id, f.nome FROM contratos_fornecedor cf JOIN fornecedores f ON f.id = cf.fornecedor_id WHERE cf.id = ?');
$checkExistente = $db->prepare('SELECT id FROM despesas_competencia WHERE contrato_fornecedor_id = ? AND competencia = ?');

$lancamentos = [];
$contratosVistos = [];
if (!$erros) {
    foreach ($itens as $item) {
        $contratoId = filter_var($item['contrato_fornecedor_id'] ?? null, FILTER_VALIDATE_INT);
        $checkContrato->execute([$contratoId ?: 0]);
        $contrato = $checkContrato->fetch();
        if (!$contrato) {
            $erros[] = 'Contrato de fornecedor não encontrado.';
            continue;
        }
        $rotulo = "{$contrato['nome']} (contrato #$contratoId)";

        if (isset($contratosVistos[$contratoId])) {
            $erros[] = "$rotulo: enviado mais de uma vez.";
            continue;
        }
        $contratosVistos[$contratoId] = true;

        $valorBase = round((float) ($item['valor_base'] ?? 0), 2);
        $valorAdicional = round((float) ($item['valor_adicional'] ?? 0), 2);
        $valor = round($valorBase + $valorAdicional, 2);
        $observacao = trim((string) ($item['observacao'] ?? ''));
        $vencimento = trim((string) ($item['vencimento'] ?? ''));
        $horas = isset($item['horas']) && is_numeric($item['horas']) && (float) $item['horas'] > 0 ? round((float) $item['horas'], 2) : null;

        if ($valorBase < 0 || $valorAdicional < 0) $erros[] = "$rotulo: valor negativo.";
        if ($valor <= 0) $erros[] = "$rotulo: valor total zerado.";
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimento)) $erros[] = "$rotulo: vencimento inválido.";
        if (mb_strlen($observacao, 'UTF-8') > 255) $erros[] = "$rotulo: observação com mais de 255 caracteres.";
        if ($valorAdicional > 0 && $observacao === '') $erros[] = "$rotulo: informe na observação o motivo do adicional.";

        $checkExistente->execute([$contratoId, $competencia]);
        if ($checkExistente->fetch()) {
            $erros[] = "$rotulo: já existe um lançamento para a competência $competencia. Exclua o existente antes de lançar de novo.";
        }

        $lancamentos[] = [
            $contratoId, $competencia, $horas, $valor,
            $valorAdicional > 0 ? $valorAdicional : null,
            $vencimento,
            ($item['calculo'] ?? '') === 'movidesk' ? 'movidesk_automatico' : 'manual',
            $observacao !== '' ? $observacao : null,
        ];
    }
}

if ($erros) {
    http_response_code(422);
    echo json_encode(['erro' => 'Dados inválidos', 'detalhes' => $erros], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db->beginTransaction();
    $stmt = $db->prepare("
        INSERT INTO despesas_competencia
            (contrato_fornecedor_id, competencia, horas_consumidas, valor, valor_adicional, vencimento, status, origem, observacao)
        VALUES (?, ?, ?, ?, ?, ?, 'a_pagar', ?, ?)
    ");
    foreach ($lancamentos as $lancamento) {
        $stmt->execute($lancamento);
    }
    $db->commit();

    echo json_encode(['sucesso' => true, 'lancados' => count($lancamentos)]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao lançar o fechamento de consultores', 'detalhe' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
