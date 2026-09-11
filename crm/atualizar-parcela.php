<?php
require_once __DIR__.'/auth.php'; require_login_api();
/**
 * Atualiza o status de UMA parcela específica. Existe principalmente pra
 * cobrir o caso de contrato lançado retroativamente: parcelas de meses
 * que já passaram (e já foram pagas por fora do sistema) precisam ser
 * marcadas como 'pago' MANUALMENTE aqui — sem isso, quando o contrato
 * for aprovado, a integração ASAAS tentaria gerar cobrança pra elas
 * também (ela só pula parcelas que NÃO estão mais em 'a_gerar').
 *
 * Nunca mexe em asaas_payment_id — só o status muda por aqui. Se uma
 * parcela já tem cobrança gerada no ASAAS, alterar o status manualmente
 * não cancela nem afeta a cobrança lá — é só um controle interno.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$db = getDb();

$parcelaId  = filter_input(INPUT_POST, 'parcela_id', FILTER_VALIDATE_INT);
$novoStatus = $_POST['status'] ?? '';

$statusValidos = ['a_gerar', 'gerado', 'pago', 'atrasado'];

if (!$parcelaId) {
    http_response_code(422);
    echo json_encode(['erro' => 'Parcela inválida.']);
    exit;
}
if (!in_array($novoStatus, $statusValidos, true)) {
    http_response_code(422);
    echo json_encode(['erro' => 'Status inválido.']);
    exit;
}

$stmt = $db->prepare('SELECT id, contrato_id, asaas_payment_id FROM parcelas WHERE id = ?');
$stmt->execute([$parcelaId]);
$parcela = $stmt->fetch();

if (!$parcela) {
    http_response_code(404);
    echo json_encode(['erro' => 'Parcela não encontrada.']);
    exit;
}

// Só avisa (não bloqueia) se a parcela já tem cobrança no ASAAS — marcar
// como 'pago' aqui não mexe na cobrança de lá, é intencional que você
// saiba disso antes de confundir os dois controles.
$avisoAsaas = $parcela['asaas_payment_id'] ? 'Esta parcela já tem cobrança gerada no ASAAS — mudar o status aqui não altera nada lá.' : null;

$db->prepare('UPDATE parcelas SET status = ? WHERE id = ?')->execute([$novoStatus, $parcelaId]);

// Se essa foi a última parcela a ficar paga, o contrato terminou de
// verdade — muda o status dele sozinho, mas só se estava 'ativo' (não
// mexe se estava rascunho/aprovado/já encerrado manualmente).
$contratoConcluido = false;
if ($novoStatus === 'pago') {
    $checkPendentes = $db->prepare("SELECT COUNT(*) AS pendentes FROM parcelas WHERE contrato_id = ? AND status != 'pago'");
    $checkPendentes->execute([$parcela['contrato_id']]);
    $pendentes = (int) $checkPendentes->fetch()['pendentes'];

    if ($pendentes === 0) {
        $checkContrato = $db->prepare("SELECT status FROM contratos WHERE id = ?");
        $checkContrato->execute([$parcela['contrato_id']]);
        $statusContrato = $checkContrato->fetch()['status'] ?? null;

        if ($statusContrato === 'ativo') {
            $db->prepare("UPDATE contratos SET status = 'concluido' WHERE id = ?")->execute([$parcela['contrato_id']]);
            $contratoConcluido = true;
        }
    }
}

echo json_encode(['sucesso' => true, 'status' => $novoStatus, 'aviso' => $avisoAsaas, 'contrato_concluido' => $contratoConcluido]);
