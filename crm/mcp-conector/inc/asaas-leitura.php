<?php
/**
 * Consulta ao ASAAS só de leitura (GET). De propósito não reaproveita o
 * AsaasClient do CRM, que também cria e altera cobranças: aqui não existe
 * nenhum caminho de código que escreva no ASAAS.
 */

if (!defined('MCP_CONECTOR')) {
    http_response_code(404);
    exit;
}

const ASAAS_STATUS_PAGO = ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH', 'DUNNING_RECEIVED'];
const ASAAS_STATUS_EM_ABERTO = ['PENDING', 'OVERDUE', 'AWAITING_RISK_ANALYSIS', 'DUNNING_REQUESTED'];

const ASAAS_STATUS_ROTULO = [
    'PENDING'                      => 'aguardando pagamento',
    'OVERDUE'                      => 'vencida',
    'RECEIVED'                     => 'recebida',
    'CONFIRMED'                    => 'confirmada (crédito ainda não caiu)',
    'RECEIVED_IN_CASH'             => 'recebida em dinheiro',
    'DUNNING_RECEIVED'             => 'recuperada (negativação)',
    'DUNNING_REQUESTED'            => 'em negativação',
    'AWAITING_RISK_ANALYSIS'       => 'em análise de risco',
    'REFUNDED'                     => 'estornada',
    'REFUND_REQUESTED'             => 'estorno solicitado',
    'REFUND_IN_PROGRESS'           => 'estorno em andamento',
    'CHARGEBACK_REQUESTED'         => 'chargeback solicitado',
    'CHARGEBACK_DISPUTE'           => 'chargeback em disputa',
    'AWAITING_CHARGEBACK_REVERSAL' => 'aguardando reversão de chargeback',
    'DELETED'                      => 'excluída no ASAAS',
    'NAO_ENCONTRADA'               => 'não encontrada no ASAAS',
];

function asaas_leitura_get(string $caminho, array $query = []): array
{
    if (!defined('ASAAS_API_KEY') || !defined('ASAAS_BASE_URL')) {
        throw new RuntimeException('ASAAS não configurado no config-crm.php');
    }
    $url = rtrim(ASAAS_BASE_URL, '/') . $caminho . ($query ? '?' . http_build_query($query) : '');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET        => true,
        CURLOPT_HTTPHEADER     => [
            'access_token: ' . ASAAS_API_KEY,
            'User-Agent: Newsiga-CRM-MCP/' . MCP_VERSAO,
        ],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 12,
    ]);
    $resposta = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($resposta === false) {
        throw new RuntimeException("Falha de conexão com o ASAAS: $erroCurl");
    }
    $dados = json_decode($resposta, true) ?? [];
    if ($status === 404) {
        return ['__nao_encontrada' => true];
    }
    if ($status >= 400) {
        throw new RuntimeException("ASAAS GET $caminho respondeu $status");
    }
    return $dados;
}

/**
 * Cobranças de um cliente do ASAAS com vencimento no intervalo (para achar
 * as avulsas de uma fatura dividida). Devolve null se o ASAAS falhar.
 */
function asaas_cobrancas_do_cliente(string $customerId, string $de, string $ate): ?array
{
    static $cache = [];
    $chave = "$customerId|$de|$ate";
    if (array_key_exists($chave, $cache)) {
        return $cache[$chave];
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $customerId)) {
        return $cache[$chave] = null;
    }
    try {
        $cobrancas = [];
        for ($offset = 0; $offset < 300; $offset += 100) {
            $lista = asaas_leitura_get('/payments', [
                'customer' => $customerId, 'dueDate[ge]' => $de, 'dueDate[le]' => $ate, 'limit' => 100, 'offset' => $offset,
            ]);
            $cobrancas = array_merge($cobrancas, $lista['data'] ?? []);
            if (empty($lista['hasMore'])) {
                break;
            }
        }
        return $cache[$chave] = $cobrancas;
    } catch (Throwable $e) {
        error_log('MCP: listagem de cobranças do cliente no ASAAS falhou: ' . $e->getMessage());
        return $cache[$chave] = null;
    }
}

/**
 * Busca no ASAAS as cobranças com esses ids. Primeiro lista as cobranças
 * por faixa de vencimento (poucas chamadas cobrem tudo); o que não vier na
 * lista (vencimento alterado no ASAAS, cobrança excluída) é buscado uma a
 * uma, dentro de um limite de tempo para não estourar os 60 s do PHP.
 *
 * Devolve ['mapa' => [payment_id => cobrança], 'erro' => ?string].
 * Cobrança não encontrada vem com status 'NAO_ENCONTRADA'. Id que não deu
 * tempo de consultar simplesmente não aparece no mapa.
 */
function asaas_cobrancas_por_id(array $ids, string $vencimentoMin, string $vencimentoMax): array
{
    $ids = array_values(array_unique(array_filter($ids)));
    if (!$ids) {
        return ['mapa' => [], 'erro' => null];
    }

    $inicio = microtime(true);
    $mapa = [];
    $erro = null;

    try {
        $offset = 0;
        for ($pagina = 0; $pagina < 10; $pagina++) {
            $lista = asaas_leitura_get('/payments', [
                'dueDate[ge]' => date('Y-m-d', strtotime("$vencimentoMin -45 days")),
                'dueDate[le]' => date('Y-m-d', strtotime("$vencimentoMax +45 days")),
                'limit'       => 100,
                'offset'      => $offset,
            ]);
            foreach ($lista['data'] ?? [] as $cobranca) {
                if (isset($cobranca['id']) && in_array($cobranca['id'], $ids, true)) {
                    $mapa[$cobranca['id']] = $cobranca;
                }
            }
            if (empty($lista['hasMore'])) {
                break;
            }
            $offset += 100;
        }

        foreach ($ids as $id) {
            if (isset($mapa[$id])) {
                continue;
            }
            if (microtime(true) - $inicio > 25) {
                $erro = 'Parte das cobranças não foi consultada no ASAAS (tempo esgotado).';
                break;
            }
            if (!preg_match('/^[A-Za-z0-9_]+$/', $id)) {
                continue;
            }
            $cobranca = asaas_leitura_get('/payments/' . $id);
            if (!empty($cobranca['__nao_encontrada'])) {
                $mapa[$id] = ['id' => $id, 'status' => 'NAO_ENCONTRADA'];
            } elseif (!empty($cobranca['deleted'])) {
                $mapa[$id] = ['status' => 'DELETED'] + $cobranca;
            } else {
                $mapa[$id] = $cobranca;
            }
        }
    } catch (Throwable $e) {
        error_log('MCP: consulta ao ASAAS falhou: ' . $e->getMessage());
        $erro = 'Não foi possível consultar o ASAAS agora; onde faltou, vale o status do CRM.';
    }

    return ['mapa' => $mapa, 'erro' => $erro];
}
