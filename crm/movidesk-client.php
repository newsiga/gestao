<?php
/**
 * Integração com o Movidesk — adaptada do movidesk.php/portal-data.php
 * já em produção na área do cliente (newsiga.com.br). Mesma lógica de
 * consulta (timeAgreement + timeAgreementConsumption), só trocando o
 * cache de arquivo por uma tabela no banco (consumo_movidesk_cache),
 * já que aqui o cache é por contrato do CRM, não por sessão de portal.
 */

require_once __DIR__ . '/db.php';

function movidesk_get(string $resource, array $parametros = []): array
{
    if (!defined('MOVIDESK_TOKEN') || !defined('MOVIDESK_BASE_URL')) {
        throw new RuntimeException('Integração com o Movidesk não configurada (MOVIDESK_TOKEN / MOVIDESK_BASE_URL).');
    }

    $parametros = ['token' => MOVIDESK_TOKEN] + $parametros;
    $url = rtrim(MOVIDESK_BASE_URL, '/') . '/' . ltrim($resource, '/') . '?' . http_build_query($parametros, '', '&', PHP_QUERY_RFC3986);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        throw new RuntimeException('Conexão com o Movidesk falhou: ' . ($erroCurl ?: 'motivo desconhecido'));
    }
    if ($status !== 200) {
        throw new RuntimeException("Movidesk respondeu HTTP $status ao consultar $resource.");
    }
    $data = json_decode((string) $body, true);
    if (!is_array($data)) {
        throw new RuntimeException('O Movidesk retornou uma resposta inválida.');
    }
    return $data;
}

/** Horas de um apontamento — usa periodStart/periodEnd se existirem, senão workTime */
function movidesk_horas_apontamento(array $entry): float
{
    $inicio = (string) ($entry['periodStart'] ?? '');
    $fim = (string) ($entry['periodEnd'] ?? '');

    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $inicio) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $fim)) {
        $segundosInicio = strtotime("1970-01-01 $inicio UTC");
        $segundosFim = strtotime("1970-01-01 $fim UTC");
        if ($segundosFim > $segundosInicio) {
            return ($segundosFim - $segundosInicio) / 3600;
        }
    }

    $workTime = (string) ($entry['workTime'] ?? '00:00:00');
    $partes = array_map('intval', explode(':', $workTime));
    return ($partes[0] ?? 0) + (($partes[1] ?? 0) / 60) + (($partes[2] ?? 0) / 3600);
}

/**
 * Busca o consumo REAL de um banco de horas (identificado pelo nome do
 * timeAgreement no Movidesk) numa competência — direto na API, sem cache.
 * Retorna o total de horas consumidas no período.
 */
function movidesk_buscar_consumo(string $nomeContratoMovidesk, string $competencia): float
{
    [$ano, $mes] = explode('-', $competencia);
    $inicio = "$competencia-01T00:00:00";
    $fim = date('Y-m-t', strtotime("$competencia-01")) . 'T23:59:59';

    $dados = movidesk_get('timeAgreementConsumption', [
        'name' => $nomeContratoMovidesk,
        'startPeriod' => $inicio,
        'endPeriod' => $fim,
    ]);
    if (isset($dados[0]) && is_array($dados[0])) {
        $dados = $dados[0];
    }

    $apontamentos = $dados['timeAppointments'] ?? [];
    $total = 0.0;
    foreach ($apontamentos as $apontamento) {
        $total += movidesk_horas_apontamento($apontamento);
    }
    return $total;
}

/**
 * Mesma coisa, mas com cache em banco (tabela consumo_movidesk_cache) —
 * evita bater na API do Movidesk toda vez que o fechamento mensal ou o
 * painel precisam do mesmo dado. TTL padrão: 1 hora.
 */
function movidesk_consumo_cache(int $contratoId, string $nomeContratoMovidesk, string $competencia, int $ttlSegundos = 3600): float
{
    $db = getDb();

    $stmt = $db->prepare('SELECT horas_consumidas, atualizado_em FROM consumo_movidesk_cache WHERE contrato_id = ? AND competencia = ?');
    $stmt->execute([$contratoId, $competencia]);
    $cache = $stmt->fetch();

    if ($cache && (time() - strtotime($cache['atualizado_em'])) < $ttlSegundos) {
        return (float) $cache['horas_consumidas'];
    }

    $horas = movidesk_buscar_consumo($nomeContratoMovidesk, $competencia);

    $db->prepare("
        INSERT INTO consumo_movidesk_cache (contrato_id, competencia, horas_consumidas)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE horas_consumidas = VALUES(horas_consumidas), atualizado_em = CURRENT_TIMESTAMP
    ")->execute([$contratoId, $competencia, $horas]);

    return $horas;
}

/**
 * Busca a definição completa de um contrato de horas no Movidesk,
 * incluindo typeActivities (franquia, R$/h normal e R$/h excedente por
 * tipo de hora/atividade) — a mesma fonte de dados que a área do
 * cliente (movidesk.php:movidesk_contract_cached) já usa.
 */
function movidesk_definicao_contrato(string $nomeContratoMovidesk): array
{
    $definicao = movidesk_get('timeAgreement', [
        'name' => $nomeContratoMovidesk,
        '$expand' => 'typeActivities',
    ]);
    if (isset($definicao[0]) && is_array($definicao[0])) {
        $definicao = $definicao[0];
    }
    return $definicao;
}

/**
 * Verifica se o banco de horas usa taxa diferenciada por tipo de
 * atividade no Movidesk (campo differentiateHoursFranchise + typeActivities
 * configurados). Contratos assim precisam do cálculo por tipo de hora
 * (movidesk_valores_diferenciados), não da fórmula simples de mínimo/excedente.
 */
function movidesk_usa_taxa_diferenciada(string $nomeContratoMovidesk): bool
{
    $definicao = movidesk_definicao_contrato($nomeContratoMovidesk);
    return ($definicao['differentiateHoursFranchise'] ?? false) === true
        && !empty($definicao['typeActivities']);
}

/**
 * Agrupa os apontamentos (timeAppointments do timeAgreementConsumption)
 * por ticket, somando as horas e guardando os "segmentos" por tipo de
 * hora — porte direto de portal_group_tickets() (portal-data.php, área
 * do cliente, já validado em produção). A ordenação por data+número de
 * ticket é o que define a ordem de consumo da franquia.
 */
function movidesk_agrupar_tickets(array $apontamentos): array
{
    $tickets = [];
    foreach ($apontamentos as $entry) {
        $numero = (string) ($entry['ticketNumber'] ?? '');
        if ($numero === '') {
            continue;
        }
        if (!isset($tickets[$numero])) {
            $tickets[$numero] = [
                'number' => $numero,
                'hours' => 0.0,
                'date' => (string) ($entry['date'] ?? ''),
                'activity' => (string) ($entry['activity'] ?? ''),
                'segments' => [],
            ];
        }
        $horas = movidesk_horas_apontamento($entry);
        $tickets[$numero]['hours'] += $horas;
        $tipo = (string) ($entry['workTypeName'] ?? '');
        if ($tipo !== '') {
            $tickets[$numero]['segments'][] = ['type' => $tipo, 'hours' => $horas];
        }
    }
    usort($tickets, static fn($a, $b) => strcmp($a['date'], $b['date']) ?: strcmp($a['number'], $b['number']));
    return array_values($tickets);
}

/**
 * Calcula o valor de cada ticket (e o total) para contratos com taxa
 * diferenciada por tipo de hora — porte direto de
 * portal_differentiated_values() (portal-data.php). Consome a franquia
 * de cada tipo de hora, ticket por ticket em ordem cronológica, cobrando
 * a taxa normal até bater a franquia daquele tipo e a taxa de excedente
 * a partir daí — replicando o mesmo motor que a área do cliente já usa
 * (e que bate exato com os relatórios do Movidesk, caso Mari Louças).
 */
function movidesk_valores_diferenciados(array $tickets, array $definicao): array
{
    $regras = [];
    foreach (($definicao['typeActivities'] ?? []) as $regra) {
        $tipo = (string) ($regra['workingTimeType'] ?? '');
        if ($tipo === '') {
            continue;
        }
        $regras[$tipo] = [
            'franchise' => (float) ($regra['franchise'] ?? 0),
            'rate' => (float) ($regra['value'] ?? 0),
            'excess_rate' => (float) ($regra['valueExceededHour'] ?? 0),
        ];
    }

    $restante = array_map(static fn(array $r): float => max(0, $r['franchise']), $regras);
    $totalPorTipo = array_fill_keys(array_keys($regras), 0.0);
    $total = 0.0;

    foreach ($tickets as &$ticket) {
        $ticket['value'] = 0.0;
        foreach (($ticket['segments'] ?? []) as $segmento) {
            $tipo = (string) $segmento['type'];
            $horas = (float) $segmento['hours'];
            $regra = $regras[$tipo] ?? null;
            if (!$regra) {
                continue;
            }
            $coberto = min($restante[$tipo] ?? 0, $horas);
            $restante[$tipo] = max(0, ($restante[$tipo] ?? 0) - $coberto);
            $excedente = max(0, $horas - $coberto);
            $taxaExcedente = $regra['excess_rate'] > 0 ? $regra['excess_rate'] : $regra['rate'];
            $valorSegmento = round(($coberto * $regra['rate']) + ($excedente * $taxaExcedente), 2);
            $ticket['value'] += $valorSegmento;
            $totalPorTipo[$tipo] += $valorSegmento;
        }
        $ticket['value'] = round($ticket['value'], 2);
        $total += $ticket['value'];
    }
    unset($ticket);

    return [$tickets, round($total, 2), $regras, $totalPorTipo];
}

/**
 * Calcula o valor a faturar de um contrato com taxa diferenciada por
 * tipo de hora, numa competência — busca a definição e o consumo direto
 * do Movidesk e aplica movidesk_valores_diferenciados(). Uso: só para
 * contratos onde movidesk_usa_taxa_diferenciada() retorna true.
 */
function movidesk_valor_diferenciado(string $nomeContratoMovidesk, string $competencia): float
{
    $inicio = "$competencia-01T00:00:00";
    $fim = date('Y-m-t', strtotime("$competencia-01")) . 'T23:59:59';

    $definicao = movidesk_definicao_contrato($nomeContratoMovidesk);

    $dados = movidesk_get('timeAgreementConsumption', [
        'name' => $nomeContratoMovidesk,
        'startPeriod' => $inicio,
        'endPeriod' => $fim,
    ]);
    if (isset($dados[0]) && is_array($dados[0])) {
        $dados = $dados[0];
    }
    $apontamentos = $dados['timeAppointments'] ?? [];

    $tickets = movidesk_agrupar_tickets($apontamentos);
    [, $total] = movidesk_valores_diferenciados($tickets, $definicao);

    return $total;
}
