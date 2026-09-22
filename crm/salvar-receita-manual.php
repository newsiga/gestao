<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Lançamento MANUAL de uma fatura (receita) pra contratos com
 * faturamento_gerenciado_por = 'manual' — parceiros que não são
 * cobrados via ASAAS (ex: HubVision, MobCode). Nunca gera cobrança
 * real nenhuma, é só um registro do que já foi combinado/recebido por
 * fora. Espelha salvar-despesa-competencia.php (mesmo padrão do lado
 * de despesa), gravando direto em `faturas` em vez de
 * `despesas_competencia`.
 *
 * Nasce em status = 'confirmado' (existe no enum de faturas.status,
 * nunca usado pelo fechamento automático — reservado pra esse caso).
 * asaas_payment_id fica NULL: é assim que o resto do sistema já
 * distingue implicitamente uma fatura sem cobrança real associada.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$contratoId  = filter_input(INPUT_POST, 'contrato_id', FILTER_VALIDATE_INT);
$competencia = trim($_POST['competencia'] ?? '');
$valor       = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
$vencimento  = trim($_POST['vencimento'] ?? '');

$erros = [];
if (!$contratoId) $erros[] = 'Contrato inválido.';
if (!preg_match('/^\d{4}-\d{2}$/', $competencia)) $erros[] = 'Competência inválida (formato esperado AAAA-MM).';
if (!$valor || $valor <= 0) $erros[] = 'Valor inválido.';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimento)) $erros[] = 'Vencimento inválido.';

if ($contratoId) {
    $check = $db->prepare("SELECT id, faturamento_gerenciado_por FROM contratos WHERE id = ?");
    $check->execute([$contratoId]);
    $contrato = $check->fetch();
    if (!$contrato) {
        $erros[] = 'Contrato não encontrado.';
    } elseif ($contrato['faturamento_gerenciado_por'] !== 'manual') {
        $erros[] = 'Este contrato é faturado pelo sistema (ASAAS) — lançamento manual só vale pra contratos marcados como "faturamento manual".';
    }
}

if ($erros) {
    http_response_code(422);
    echo json_encode(['erro' => 'Dados inválidos', 'detalhes' => $erros]);
    exit;
}

try {
    $stmt = $db->prepare("
        INSERT INTO faturas (contrato_id, competencia, valor, valor_liquido, valor_imposto, vencimento, status)
        VALUES (?, ?, ?, ?, 0, ?, 'confirmado')
    ");
    $stmt->execute([$contratoId, $competencia, $valor, $valor, $vencimento]);
    $faturaId = (int) $db->lastInsertId();

    echo json_encode(['sucesso' => true, 'fatura_id' => $faturaId]);
} catch (Throwable $e) {
    // uq_faturas_contrato_competencia bloqueia duplicar a mesma
    // competência pro mesmo contrato — transforma o erro do banco numa
    // mensagem legível em vez de vazar SQL cru.
    if (str_contains($e->getMessage(), 'uq_faturas_contrato_competencia')) {
        http_response_code(422);
        echo json_encode(['erro' => "Já existe um lançamento para a competência $competencia neste contrato. Edite ou exclua o existente em vez de lançar de novo."]);
        exit;
    }
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao gravar receita manual', 'detalhe' => $e->getMessage()]);
}
