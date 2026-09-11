<?php
/**
 * Recebe os eventos de webhook do ASAAS (pagamento confirmado, atrasado,
 * etc.) e atualiza o status da parcela correspondente no nosso banco —
 * sem precisar ficar checando manualmente no painel do ASAAS.
 *
 * Precisa ser cadastrado no painel do ASAAS: Configurações > Integrações >
 * Webhooks, apontando pra https://crm.newsiga.com.br/webhook-asaas.php,
 * com um "Token de acesso" configurado (o mesmo valor de ASAAS_WEBHOOK_TOKEN
 * aqui embaixo).
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

// Valida o token que o ASAAS envia de volta — sem isso, qualquer um na
// internet poderia mandar POST fingindo ser o ASAAS e marcar parcelas
// como pagas por engano.
$tokenRecebido = $_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] ?? '';
if (!defined('ASAAS_WEBHOOK_TOKEN') || !hash_equals(ASAAS_WEBHOOK_TOKEN, $tokenRecebido)) {
    http_response_code(401);
    echo json_encode(['erro' => 'Token inválido.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$evento = $payload['event'] ?? null;
$cobranca = $payload['payment'] ?? null;

if (!$evento || !$cobranca) {
    http_response_code(400);
    echo json_encode(['erro' => 'Payload inválido.']);
    exit;
}

// externalReference vem no formato "parcela:123" (definido lá no
// confirmar-asaas.php, quando a cobrança foi criada)
$ref = $cobranca['externalReference'] ?? '';
if (!str_starts_with($ref, 'parcela:')) {
    // Evento de algo que não é uma parcela nossa (ex: cobrança avulsa
    // criada direto no painel do ASAAS) — ignora sem erro.
    http_response_code(200);
    echo json_encode(['recebido' => true, 'ignorado' => true]);
    exit;
}
$parcelaId = (int) substr($ref, 8);

$mapaEventos = [
    'PAYMENT_CONFIRMED' => 'pago',
    'PAYMENT_RECEIVED' => 'pago',
    'PAYMENT_OVERDUE' => 'atrasado',
];

$novoStatus = $mapaEventos[$evento] ?? null;

try {
    $db = getDb();

    if ($novoStatus) {
        $db->prepare('UPDATE parcelas SET status = ? WHERE id = ?')->execute([$novoStatus, $parcelaId]);

        // Mesma lógica de encerramento automático do atualizar-parcela.php:
        // se essa foi a última parcela a virar 'pago', o contrato inteiro
        // passa pra 'encerrado' sozinho.
        if ($novoStatus === 'pago') {
            $stmt = $db->prepare('SELECT contrato_id FROM parcelas WHERE id = ?');
            $stmt->execute([$parcelaId]);
            $parcela = $stmt->fetch();

            if ($parcela) {
                $checkTodas = $db->prepare("SELECT COUNT(*) AS total, SUM(status = 'pago') AS pagas FROM parcelas WHERE contrato_id = ?");
                $checkTodas->execute([$parcela['contrato_id']]);
                $contagem = $checkTodas->fetch();

                if ($contagem && (int) $contagem['total'] > 0 && (int) $contagem['total'] === (int) $contagem['pagas']) {
                    $db->prepare("UPDATE contratos SET status = 'encerrado' WHERE id = ? AND status != 'encerrado'")
                       ->execute([$parcela['contrato_id']]);
                }
            }
        }
    } elseif ($evento === 'PAYMENT_UPDATED') {
        // Alguém mudou valor e/ou vencimento direto no painel do ASAAS —
        // reflete de volta pro nosso banco, pra não ficar dessincronizado.
        // Não mexe em status aqui, só nos dois campos que o ASAAS reportou.
        $novoValor = isset($cobranca['value']) ? (float) $cobranca['value'] : null;
        $novoVencimento = $cobranca['dueDate'] ?? null;

        if ($novoValor !== null && $novoVencimento !== null) {
            $db->prepare('UPDATE parcelas SET valor = ?, vencimento = ? WHERE id = ?')
               ->execute([$novoValor, $novoVencimento, $parcelaId]);
        }
    }
    // Outros eventos fora do mapa (ex: PAYMENT_DELETED) são reconhecidos
    // mas não alteram nada por enquanto — evita agir sobre algo que ainda
    // não modelamos direito.

    http_response_code(200);
    echo json_encode(['recebido' => true, 'evento' => $evento, 'parcela_id' => $parcelaId, 'status_aplicado' => $novoStatus]);
} catch (Throwable $e) {
    // Mesmo com erro interno, responde 200 pro ASAAS não ficar reenviando
    // o mesmo evento repetidamente — mas registra o erro pra investigar depois.
    error_log('Falha no webhook ASAAS: ' . $e->getMessage());
    http_response_code(200);
    echo json_encode(['recebido' => true, 'erro_interno' => true]);
}
