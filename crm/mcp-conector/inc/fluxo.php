<?php
/**
 * Cálculos de fluxo de caixa usados pelas tools do conector MCP. Só SELECT
 * no banco do CRM e GET no ASAAS.
 *
 * Regras (as mesmas das telas do CRM, para os números baterem):
 * - O mês de uma receita ou despesa é o mês do VENCIMENTO, não da
 *   competência (crm-newsiga-painel.php, faturamento-mensal.php).
 * - Receita prevista do mês = faturas de contratos ativos com vencimento no
 *   mês + parcelas de projeto com vencimento no mês + contratos de
 *   mensalidade fixa ativos que ainda não têm fatura no mês (pelo valor do
 *   contrato). Contrato por hora sem fatura ainda não entra: o valor só
 *   existe depois do fechamento.
 * - Recebimento: o webhook do ASAAS só atualiza parcelas de projeto; as
 *   faturas mensais ficam em 'gerado' no CRM mesmo depois de pagas. Por
 *   isso o recebido de fatura vem do ASAAS, consultado na hora.
 * - Despesa prevista do mês = despesas lançadas com vencimento no mês +,
 *   do mês atual em diante, os contratos fixos de fornecedor ainda sem
 *   lançamento no mês. Esse segundo item não existe na tela de Despesas:
 *   entra aqui para a projeção não sair otimista.
 */

if (!defined('MCP_CONECTOR')) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/asaas-leitura.php';

// ---------------------------------------------------------------- datas

function fx_hoje(): string
{
    return date('Y-m-d');
}

function fx_mes_atual(): string
{
    return date('Y-m');
}

function fx_validar_mes($mes): string
{
    if ($mes === null || $mes === '') {
        return fx_mes_atual();
    }
    if (!is_string($mes) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
        throw new InvalidArgumentException('O parâmetro "mes" deve estar no formato AAAA-MM, por exemplo 2026-11.');
    }
    return $mes;
}

function fx_validar_data($data, string $padrao): string
{
    if ($data === null || $data === '') {
        return $padrao;
    }
    if (!is_string($data) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $data, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        throw new InvalidArgumentException('A data deve estar no formato AAAA-MM-DD, por exemplo 2026-11-30.');
    }
    return $data;
}

/** [primeiro dia, último dia] do mês AAAA-MM. */
function fx_limites_mes(string $mes): array
{
    $inicio = "$mes-01";
    return [$inicio, date('Y-m-t', strtotime($inicio))];
}

/** Data do dia de vencimento dentro do mês, limitada ao último dia (31 em fevereiro vira 28/29). */
function fx_dia_no_mes(string $mes, $dia): string
{
    $ultimo = (int) date('t', strtotime("$mes-01"));
    return sprintf('%s-%02d', $mes, max(1, min((int) ($dia ?: 1), $ultimo)));
}

/** Meses AAAA-MM de $de até $ate, inclusive. */
function fx_meses_entre(string $de, string $ate): array
{
    $meses = [];
    $cursor = strtotime("$de-01");
    $fim = strtotime("$ate-01");
    while ($cursor <= $fim && count($meses) < 24) {
        $meses[] = date('Y-m', $cursor);
        $cursor = strtotime('+1 month', $cursor);
    }
    return $meses;
}

function fx_dias_entre(string $de, string $ate): int
{
    return (int) round((strtotime($ate) - strtotime($de)) / 86400);
}

function fx_r($valor): float
{
    return round((float) $valor, 2);
}

function fx_moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function fx_somar(array $itens, string $campo, ?callable $filtro = null): float
{
    $total = 0.0;
    foreach ($itens as $item) {
        if ($filtro === null || $filtro($item)) {
            $total += (float) $item[$campo];
        }
    }
    return fx_r($total);
}

// ---------------------------------------------------------------- receitas

/**
 * Faturas com vencimento no intervalo. $soContratosAtivos replica a regra
 * da previsão do painel; $soNaoPagas filtra o status do CRM (lembrando que
 * fatura de sistema nunca vira 'pago' no CRM, ver cabeçalho).
 */
function fx_faturas(string $ini, string $fim, bool $soContratosAtivos, bool $soNaoPagas): array
{
    $sql = "
        SELECT f.id, f.contrato_id, f.competencia, f.valor, f.vencimento, f.status, f.asaas_payment_id,
               c.tipo AS contrato_tipo, c.descricao AS contrato_descricao, c.faturamento_gerenciado_por,
               cl.nome AS cliente, cl.asaas_customer_id
        FROM " . crm_t('faturas') . " f
        JOIN " . crm_t('contratos') . " c ON c.id = f.contrato_id
        JOIN " . crm_t('clientes') . " cl ON cl.id = c.cliente_id
        WHERE f.vencimento BETWEEN ? AND ?";
    if ($soContratosAtivos) {
        $sql .= " AND c.status = 'ativo'";
    }
    if ($soNaoPagas) {
        $sql .= " AND f.status <> 'pago'";
    }
    $sql .= ' ORDER BY f.vencimento, cl.nome';
    $stmt = mcp_db()->prepare($sql);
    $stmt->execute([$ini, $fim]);

    return array_map(fn($f) => [
        'origem'           => 'fatura',
        'id'               => (int) $f['id'],
        'cliente'          => $f['cliente'],
        'contrato_id'      => (int) $f['contrato_id'],
        'descricao'        => trim(str_replace('_', ' ', $f['contrato_tipo']) . ($f['contrato_descricao'] ? ' · ' . $f['contrato_descricao'] : '')),
        'competencia'      => $f['competencia'],
        'vencimento'       => $f['vencimento'],
        'valor'            => fx_r($f['valor']),
        'status_crm'       => $f['status'],
        'asaas_payment_id' => $f['asaas_payment_id'] ?: null,
        'asaas_customer_id' => $f['asaas_customer_id'] ?: null,
        'gerenciado_por'   => $f['faturamento_gerenciado_por'],
    ], $stmt->fetchAll());
}

function fx_parcelas(string $ini, string $fim, bool $soNaoPagas): array
{
    $sql = "
        SELECT p.id, p.contrato_id, p.numero, p.valor, p.vencimento, p.status, p.asaas_payment_id,
               c.descricao AS contrato_descricao, cl.nome AS cliente, cl.asaas_customer_id
        FROM " . crm_t('parcelas') . " p
        JOIN " . crm_t('contratos') . " c ON c.id = p.contrato_id
        JOIN " . crm_t('clientes') . " cl ON cl.id = c.cliente_id
        WHERE p.vencimento BETWEEN ? AND ?";
    if ($soNaoPagas) {
        $sql .= " AND p.status <> 'pago'";
    }
    $sql .= ' ORDER BY p.vencimento, cl.nome';
    $stmt = mcp_db()->prepare($sql);
    $stmt->execute([$ini, $fim]);

    return array_map(fn($p) => [
        'origem'           => 'parcela',
        'id'               => (int) $p['id'],
        'cliente'          => $p['cliente'],
        'contrato_id'      => (int) $p['contrato_id'],
        'descricao'        => 'projeto parcelado · parcela ' . $p['numero'] . ($p['contrato_descricao'] ? ' · ' . $p['contrato_descricao'] : ''),
        'competencia'      => null,
        'vencimento'       => $p['vencimento'],
        'valor'            => fx_r($p['valor']),
        'status_crm'       => $p['status'],
        'asaas_payment_id' => $p['asaas_payment_id'] ?: null,
        'asaas_customer_id' => $p['asaas_customer_id'] ?: null,
        'gerenciado_por'   => 'sistema',
    ], $stmt->fetchAll());
}

/** Contratos de mensalidade fixa ativos sem fatura com vencimento no mês: entram pelo valor do contrato. */
function fx_fixos_previstos(string $mes): array
{
    [$ini, $fim] = fx_limites_mes($mes);
    $stmt = mcp_db()->prepare("
        SELECT c.id, c.valor, c.dia_vencimento, c.descricao, c.faturamento_gerenciado_por, cl.nome AS cliente
        FROM " . crm_t('contratos') . " c
        JOIN " . crm_t('clientes') . " cl ON cl.id = c.cliente_id
        WHERE c.status = 'ativo' AND c.tipo = 'mensalidade_fixa'
          AND NOT EXISTS (
              SELECT 1 FROM " . crm_t('faturas') . " f
              WHERE f.contrato_id = c.id AND f.vencimento BETWEEN ? AND ?
          )
        ORDER BY c.dia_vencimento, cl.nome
    ");
    $stmt->execute([$ini, $fim]);

    return array_map(fn($c) => [
        'origem'           => 'previsto',
        'id'               => null,
        'cliente'          => $c['cliente'],
        'contrato_id'      => (int) $c['id'],
        'descricao'        => 'mensalidade fixa' . ($c['descricao'] ? ' · ' . $c['descricao'] : ''),
        'competencia'      => null,
        'vencimento'       => fx_dia_no_mes($mes, $c['dia_vencimento']),
        'valor'            => fx_r($c['valor']),
        'status_crm'       => 'previsto',
        'asaas_payment_id' => null,
        'gerenciado_por'   => $c['faturamento_gerenciado_por'],
    ], $stmt->fetchAll());
}

/** Contratos por hora ativos ainda sem fatura no mês (não entram na previsão; viram observação). */
function fx_contratos_hora_sem_fatura(string $mes): array
{
    [$ini, $fim] = fx_limites_mes($mes);
    $stmt = mcp_db()->prepare("
        SELECT c.id, c.tipo, cl.nome AS cliente
        FROM " . crm_t('contratos') . " c
        JOIN " . crm_t('clientes') . " cl ON cl.id = c.cliente_id
        WHERE c.status = 'ativo' AND c.tipo IN ('banco_horas_minimo', 'hora_aberta', 'banco_horas_consumo')
          AND NOT EXISTS (
              SELECT 1 FROM " . crm_t('faturas') . " f
              WHERE f.contrato_id = c.id AND f.vencimento BETWEEN ? AND ?
          )
        ORDER BY cl.nome
    ");
    $stmt->execute([$ini, $fim]);
    return array_map(fn($c) => $c['cliente'] . ' (' . str_replace('_', ' ', $c['tipo']) . ')', $stmt->fetchAll());
}

/**
 * Cruza as receitas com o ASAAS e calcula, item a item: valor recebido,
 * valor em aberto, situação e dias de atraso. Devolve o aviso de falha do
 * ASAAS, se houver.
 */
function fx_enriquecer_receitas(array &$itens): ?string
{
    $hoje = fx_hoje();
    $ids = [];
    $min = null;
    $max = null;
    foreach ($itens as $item) {
        if ($item['asaas_payment_id'] && $item['status_crm'] !== 'pago') {
            $ids[] = $item['asaas_payment_id'];
            $min = $min === null || $item['vencimento'] < $min ? $item['vencimento'] : $min;
            $max = $max === null || $item['vencimento'] > $max ? $item['vencimento'] : $max;
        }
    }
    $consulta = $ids ? asaas_cobrancas_por_id($ids, $min, $max) : ['mapa' => [], 'erro' => null];
    $mapa = $consulta['mapa'];
    $usadas = []; // cobranças avulsas já atribuídas a alguma fatura dividida

    foreach ($itens as &$item) {
        $valor = (float) $item['valor'];
        $recebido = 0.0;
        $vencimentoEfetivo = $item['vencimento'];
        $observacoes = [];
        $item['status_asaas'] = null;

        if ($item['origem'] === 'previsto') {
            $observacoes[] = $item['gerenciado_por'] === 'sistema'
                ? 'Fatura ainda não gerada (o fechamento mensal gera no início do mês).'
                : 'Valor do contrato; o pagamento não é acompanhado pelo CRM (faturamento ' . str_replace('_', ' ', $item['gerenciado_por']) . ').';
        } elseif ($item['status_crm'] === 'pago') {
            $recebido = $valor;
        } elseif ($item['asaas_payment_id']) {
            $cobranca = $mapa[$item['asaas_payment_id']] ?? null;
            if ($cobranca === null) {
                $observacoes[] = 'Status no ASAAS não consultado; vale o status do CRM.';
            } else {
                $statusAsaas = $cobranca['status'] ?? 'DESCONHECIDO';
                $item['status_asaas'] = ASAAS_STATUS_ROTULO[$statusAsaas] ?? strtolower($statusAsaas);
                $valorAsaas = isset($cobranca['value']) ? (float) $cobranca['value'] : null;
                if (!empty($cobranca['dueDate'])) {
                    $vencimentoEfetivo = $cobranca['dueDate'];
                    if ($cobranca['dueDate'] !== $item['vencimento']) {
                        $observacoes[] = 'Vencimento no ASAAS: ' . $cobranca['dueDate'] . '.';
                    }
                }
                if (in_array($statusAsaas, ASAAS_STATUS_PAGO, true)) {
                    $recebido = $valorAsaas === null ? $valor : min($valor, $valorAsaas);
                    if (!empty($cobranca['paymentDate'] ?? $cobranca['clientPaymentDate'] ?? null)) {
                        $item['data_pagamento'] = $cobranca['paymentDate'] ?? $cobranca['clientPaymentDate'];
                    }
                } elseif (!in_array($statusAsaas, ASAAS_STATUS_EM_ABERTO, true)) {
                    $observacoes[] = 'Cobrança no ASAAS está "' . $item['status_asaas'] . '": conferir.';
                }
                $pago = in_array($statusAsaas, ASAAS_STATUS_PAGO, true);
                if ($valorAsaas !== null && $pago) {
                    $item['valor_pago_asaas'] = fx_r($valorAsaas);
                }
                if ($valorAsaas !== null && $valor - $valorAsaas >= 0.01) {
                    // Fatura dividida no ASAAS: procura as partes nas cobranças avulsas do mesmo cliente.
                    $divisao = fx_completar_com_avulsas($item, $valor - $valorAsaas, $usadas);
                    $recebido += $divisao['recebido'];
                    $abertos = $divisao['vencimentos_abertos'];
                    if (!$pago) {
                        $abertos[] = $vencimentoEfetivo;
                    }
                    if ($abertos) {
                        $vencimentoEfetivo = min($abertos);
                    }
                    $observacoes[] = 'Cobrança vinculada no ASAAS vale ' . fx_moeda($valorAsaas) . ', menos que a fatura do CRM (fatura dividida ou editada no ASAAS). '
                        . $divisao['texto'];
                } elseif ($valorAsaas !== null && $valorAsaas - $valor >= 0.01) {
                    // Pagamento em atraso: o ASAAS soma juros e multa ao valor da cobrança.
                    $observacoes[] = $pago
                        ? 'Pago com acréscimo de ' . fx_moeda($valorAsaas - $valor) . ' (juros/multa por atraso); total pago no ASAAS: ' . fx_moeda($valorAsaas) . '.'
                        : 'Valor no ASAAS (' . fx_moeda($valorAsaas) . ') é maior que no CRM: pode incluir juros/multa ou ter sido editado no ASAAS.';
                }
            }
        } elseif ($item['status_crm'] === 'a_gerar') {
            $observacoes[] = 'Cobrança ainda não emitida no ASAAS.';
        } elseif ($item['gerenciado_por'] === 'sistema') {
            $observacoes[] = 'Marcada como "' . $item['status_crm'] . '" no CRM, mas sem cobrança do ASAAS vinculada: não dá para '
                . 'saber se foi paga. Conferir no ASAAS e, se paga, marcar como paga no CRM.';
        } elseif ($item['gerenciado_por'] === 'manual') {
            $observacoes[] = 'Faturamento manual, fora do ASAAS; vale o status do CRM.';
        }

        $recebido = min($valor, $recebido);
        $aberto = max(0.0, $valor - $recebido);
        $item['valor_recebido'] = fx_r($recebido);
        $item['valor_em_aberto'] = fx_r($aberto);
        $item['vencimento_efetivo'] = $vencimentoEfetivo;

        if ($aberto < 0.01) {
            $item['situacao'] = 'recebido';
        } elseif ($item['origem'] === 'previsto') {
            $item['situacao'] = 'previsto';
        } elseif ($vencimentoEfetivo < $hoje) {
            $item['situacao'] = $recebido > 0 ? 'parcialmente_recebido_vencido' : 'vencido';
            $item['dias_atraso'] = fx_dias_entre($vencimentoEfetivo, $hoje);
        } else {
            $item['situacao'] = $recebido > 0 ? 'parcialmente_recebido' : 'a_receber';
        }
        if ($observacoes) {
            $item['observacao'] = implode(' ', $observacoes);
        }
        unset($item['gerenciado_por'], $item['asaas_customer_id']);
    }
    unset($item);

    return $consulta['erro'];
}

/**
 * Fatura dividida direto no ASAAS (ex.: Dragão e Mari Louças, 2026-09): a
 * cobrança vinculada passa a valer só uma parte, e o resto vai para
 * cobranças avulsas criadas à mão, sem vínculo com o CRM. Esta função
 * procura essas avulsas: mesmo cliente no ASAAS, sem externalReference,
 * fora de assinatura, com vencimento de 15 dias antes a 120 dias depois do
 * vencimento da fatura, somando no máximo o valor que falta.
 *
 * É uma heurística: uma avulsa de outro assunto do mesmo cliente nesse
 * período pode ser contada. Por isso o texto devolvido lista cada cobrança
 * usada, para conferência. $usadas evita usar a mesma avulsa duas vezes.
 */
function fx_completar_com_avulsas(array $item, float $falta, array &$usadas): array
{
    $resultado = ['recebido' => 0.0, 'vencimentos_abertos' => [], 'texto' => ''];
    $semCobranca = $falta;
    $cliente = $item['asaas_customer_id'] ?? null;

    $lista = $cliente ? asaas_cobrancas_do_cliente(
        $cliente,
        date('Y-m-d', strtotime($item['vencimento'] . ' -15 days')),
        date('Y-m-d', strtotime($item['vencimento'] . ' +120 days'))
    ) : null;

    if ($lista === null) {
        $resultado['vencimentos_abertos'][] = $item['vencimento'];
        $resultado['texto'] = 'Não foi possível procurar as cobranças avulsas do cliente no ASAAS; o restante ('
            . fx_moeda($falta) . ') aparece em aberto.';
        return $resultado;
    }

    $candidatas = array_filter($lista, fn($c) =>
        !empty($c['id']) && $c['id'] !== $item['asaas_payment_id'] && !isset($usadas[$c['id']])
        && empty($c['externalReference']) && empty($c['subscription']) && empty($c['deleted'])
        && in_array($c['status'] ?? '', array_merge(ASAAS_STATUS_PAGO, ASAAS_STATUS_EM_ABERTO), true));
    usort($candidatas, fn($a, $b) => [$a['dueDate'] ?? '', $a['id']] <=> [$b['dueDate'] ?? '', $b['id']]);

    $partes = [];
    foreach ($candidatas as $c) {
        if ($semCobranca < 0.01) {
            break;
        }
        // Paga com atraso, o value inclui juros/multa; o valor original é o que conta para a divisão.
        $base = (float) (!empty($c['originalValue']) ? $c['originalValue'] : ($c['value'] ?? 0));
        if ($base <= 0 || $base > $semCobranca + 0.01) {
            continue;
        }
        $usadas[$c['id']] = true;
        $semCobranca -= $base;
        $pago = in_array($c['status'], ASAAS_STATUS_PAGO, true);
        if ($pago) {
            $resultado['recebido'] += $base;
        } else {
            $resultado['vencimentos_abertos'][] = $c['dueDate'];
        }
        $partes[] = $c['id'] . ' de ' . fx_moeda($base) . ', venc. ' . $c['dueDate'] . ', '
            . (ASAAS_STATUS_ROTULO[$c['status']] ?? strtolower($c['status']))
            . ($pago && !empty($c['paymentDate']) ? ' em ' . $c['paymentDate'] : '');
    }

    $texto = $partes
        ? 'Cobranças avulsas do mesmo cliente consideradas como o restante (identificadas por cliente e vencimento próximo; conferir): '
            . implode('; ', $partes) . '.'
        : 'Nenhuma cobrança avulsa do cliente encontrada no ASAAS para o restante.';
    if ($semCobranca >= 0.01) {
        $resultado['vencimentos_abertos'][] = $item['vencimento'];
        $texto .= ' Sem cobrança encontrada para ' . fx_moeda($semCobranca) . '.';
    }
    $resultado['texto'] = $texto;
    return $resultado;
}

// ---------------------------------------------------------------- despesas

function fx_despesas(string $ini, string $fim, bool $soNaoPagas): array
{
    $sql = "
        SELECT dc.id, dc.competencia, dc.valor, dc.vencimento, dc.status, dc.observacao,
               cf.descricao AS contrato_descricao, f.nome AS fornecedor, f.categoria
        FROM " . crm_t('despesas_competencia') . " dc
        JOIN " . crm_t('contratos_fornecedor') . " cf ON cf.id = dc.contrato_fornecedor_id
        JOIN " . crm_t('fornecedores') . " f ON f.id = cf.fornecedor_id
        WHERE dc.vencimento BETWEEN ? AND ?";
    if ($soNaoPagas) {
        $sql .= " AND dc.status <> 'pago'";
    }
    $sql .= ' ORDER BY dc.vencimento, f.nome';
    $stmt = mcp_db()->prepare($sql);
    $stmt->execute([$ini, $fim]);
    $hoje = fx_hoje();

    return array_map(function ($d) use ($hoje) {
        $item = [
            'origem'      => 'lancada',
            'id'          => (int) $d['id'],
            'fornecedor'  => $d['fornecedor'],
            'categoria'   => $d['categoria'] ?: 'Sem categoria',
            'descricao'   => $d['contrato_descricao'],
            'competencia' => $d['competencia'],
            'vencimento'  => $d['vencimento'],
            'valor'       => fx_r($d['valor']),
            'status'      => $d['status'],
        ];
        if ($d['status'] === 'pago') {
            $item['situacao'] = 'pago';
        } elseif ($d['vencimento'] < $hoje) {
            $item['situacao'] = 'vencida';
            $item['dias_atraso'] = fx_dias_entre($d['vencimento'], $hoje);
        } else {
            $item['situacao'] = 'a_pagar';
        }
        if ($d['observacao']) {
            $item['observacao'] = $d['observacao'];
        }
        return $item;
    }, $stmt->fetchAll());
}

/** Contratos fixos de fornecedor ativos sem despesa lançada com vencimento no mês. */
function fx_despesas_fixas_previstas(string $mes): array
{
    [$ini, $fim] = fx_limites_mes($mes);
    $stmt = mcp_db()->prepare("
        SELECT cf.id, cf.valor, cf.dia_vencimento, cf.descricao, f.nome AS fornecedor, f.categoria
        FROM " . crm_t('contratos_fornecedor') . " cf
        JOIN " . crm_t('fornecedores') . " f ON f.id = cf.fornecedor_id
        WHERE cf.status = 'ativo' AND cf.tipo = 'mensalidade_fixa' AND f.status = 'ativo' AND cf.valor IS NOT NULL
          AND NOT EXISTS (
              SELECT 1 FROM " . crm_t('despesas_competencia') . " dc
              WHERE dc.contrato_fornecedor_id = cf.id AND dc.vencimento BETWEEN ? AND ?
          )
        ORDER BY cf.dia_vencimento, f.nome
    ");
    $stmt->execute([$ini, $fim]);

    return array_map(fn($c) => [
        'origem'      => 'prevista_sem_lancamento',
        'id'          => null,
        'fornecedor'  => $c['fornecedor'],
        'categoria'   => $c['categoria'] ?: 'Sem categoria',
        'descricao'   => $c['descricao'],
        'competencia' => null,
        'vencimento'  => fx_dia_no_mes($mes, $c['dia_vencimento']),
        'valor'       => fx_r($c['valor']),
        'status'      => 'previsto',
        'situacao'    => 'prevista',
        'observacao'  => 'Contrato fixo do fornecedor ainda sem despesa lançada no mês; entra pelo valor do contrato.',
    ], $stmt->fetchAll());
}

// ---------------------------------------------------------------- consultas das tools

function fx_resumo_mes(string $mes): array
{
    [$ini, $fim] = fx_limites_mes($mes);

    $receitas = array_merge(fx_faturas($ini, $fim, true, false), fx_parcelas($ini, $fim, false), fx_fixos_previstos($mes));
    usort($receitas, fn($a, $b) => [$a['vencimento'], $a['cliente']] <=> [$b['vencimento'], $b['cliente']]);
    $avisoAsaas = fx_enriquecer_receitas($receitas);

    $despesas = fx_despesas($ini, $fim, false);
    if ($mes >= fx_mes_atual()) {
        $despesas = array_merge($despesas, fx_despesas_fixas_previstas($mes));
        usort($despesas, fn($a, $b) => [$a['vencimento'], $a['fornecedor']] <=> [$b['vencimento'], $b['fornecedor']]);
    }

    $receitaPrevista = fx_somar($receitas, 'valor');
    $receitaRecebida = fx_somar($receitas, 'valor_recebido');
    $despesaPrevista = fx_somar($despesas, 'valor');
    $despesaPaga = fx_somar($despesas, 'valor', fn($d) => $d['situacao'] === 'pago');

    $observacoes = [];
    $semFatura = fx_contratos_hora_sem_fatura($mes);
    if ($semFatura) {
        $observacoes[] = 'Contratos por hora ainda sem fatura no mês não entram na receita prevista (o valor só existe depois do fechamento): ' . implode(', ', $semFatura) . '.';
    }
    if ($avisoAsaas) {
        $observacoes[] = $avisoAsaas;
    }
    $observacoes[] = 'O mês de cada lançamento é o do vencimento. O CRM não tem saldo bancário: o saldo aqui é só o resultado do mês.';

    return [
        'mes'      => $mes,
        'periodo'  => ['inicio' => $ini, 'fim' => $fim],
        'receita'  => [
            'prevista'          => $receitaPrevista,
            'recebida'          => $receitaRecebida,
            'a_receber'         => fx_somar($receitas, 'valor_em_aberto', fn($r) => !str_contains($r['situacao'], 'vencido')),
            'vencida_nao_paga'  => fx_somar($receitas, 'valor_em_aberto', fn($r) => str_contains($r['situacao'], 'vencido')),
            'itens'             => $receitas,
        ],
        'despesa'  => [
            'prevista'                 => $despesaPrevista,
            'paga'                     => $despesaPaga,
            'em_aberto'                => fx_r($despesaPrevista - $despesaPaga),
            'vencida_nao_paga'         => fx_somar($despesas, 'valor', fn($d) => $d['situacao'] === 'vencida'),
            'fixa_sem_lancamento'      => fx_somar($despesas, 'valor', fn($d) => $d['origem'] === 'prevista_sem_lancamento'),
            'itens'                    => $despesas,
        ],
        'saldo_projetado' => fx_r($receitaPrevista - $despesaPrevista),
        'saldo_realizado' => fx_r($receitaRecebida - $despesaPaga),
        'observacoes'     => $observacoes,
    ];
}

function fx_despesas_a_vencer(string $ateData): array
{
    $hoje = fx_hoje();
    $despesas = fx_despesas('2000-01-01', $ateData, true);
    if ($ateData >= $hoje) {
        foreach (fx_meses_entre(fx_mes_atual(), substr($ateData, 0, 7)) as $mes) {
            foreach (fx_despesas_fixas_previstas($mes) as $prevista) {
                if ($prevista['vencimento'] <= $ateData) {
                    $despesas[] = $prevista;
                }
            }
        }
    }
    usort($despesas, fn($a, $b) => [$a['vencimento'], $a['fornecedor']] <=> [$b['vencimento'], $b['fornecedor']]);

    return [
        'ate_data'         => $ateData,
        'total'            => fx_somar($despesas, 'valor'),
        'total_vencido'    => fx_somar($despesas, 'valor', fn($d) => $d['situacao'] === 'vencida'),
        'total_a_vencer'   => fx_somar($despesas, 'valor', fn($d) => $d['situacao'] !== 'vencida'),
        'quantidade'       => count($despesas),
        'despesas'         => $despesas,
        'observacoes'      => [
            'Inclui despesas vencidas e não pagas de qualquer mês anterior.',
            'Itens com origem "prevista_sem_lancamento" são contratos fixos de fornecedor ainda não lançados no CRM.',
        ],
    ];
}

/**
 * Receitas em aberto até a data: faturas e parcelas não recebidas (de
 * qualquer mês anterior também) e, do mês atual até a data, as
 * mensalidades fixas ainda sem fatura.
 */
function fx_receitas_em_aberto(string $ateData, bool $incluirPrevistos): array
{
    $receitas = array_merge(fx_faturas('2000-01-01', $ateData, false, true), fx_parcelas('2000-01-01', $ateData, true));
    if ($incluirPrevistos && $ateData >= fx_hoje()) {
        foreach (fx_meses_entre(fx_mes_atual(), substr($ateData, 0, 7)) as $mes) {
            foreach (fx_fixos_previstos($mes) as $previsto) {
                if ($previsto['vencimento'] <= $ateData) {
                    $receitas[] = $previsto;
                }
            }
        }
    }
    $aviso = fx_enriquecer_receitas($receitas);
    $receitas = array_values(array_filter($receitas, fn($r) => $r['valor_em_aberto'] >= 0.01));
    usort($receitas, fn($a, $b) => [$a['vencimento_efetivo'], $a['cliente']] <=> [$b['vencimento_efetivo'], $b['cliente']]);
    return [$receitas, $aviso];
}

function fx_receitas_a_receber(string $ateData): array
{
    [$receitas, $aviso] = fx_receitas_em_aberto($ateData, true);
    $observacoes = ['Inclui cobranças vencidas e não pagas de meses anteriores (situação "vencido").'];
    if ($aviso) {
        $observacoes[] = $aviso;
    }
    return [
        'ate_data'        => $ateData,
        'total'           => fx_somar($receitas, 'valor_em_aberto'),
        'total_vencido'   => fx_somar($receitas, 'valor_em_aberto', fn($r) => str_contains($r['situacao'], 'vencido')),
        'total_a_vencer'  => fx_somar($receitas, 'valor_em_aberto', fn($r) => !str_contains($r['situacao'], 'vencido')),
        'quantidade'      => count($receitas),
        'receitas'        => $receitas,
        'observacoes'     => $observacoes,
    ];
}

function fx_inadimplencia(): array
{
    $ontem = date('Y-m-d', strtotime('-1 day'));
    [$receitas, $aviso] = fx_receitas_em_aberto($ontem, false);
    $vencidas = array_values(array_filter($receitas, fn($r) => str_contains($r['situacao'], 'vencido')));

    $porCliente = [];
    foreach ($vencidas as $r) {
        $porCliente[$r['cliente']] ??= ['cliente' => $r['cliente'], 'total' => 0.0, 'quantidade' => 0, 'maior_atraso_dias' => 0];
        $porCliente[$r['cliente']]['total'] = fx_r($porCliente[$r['cliente']]['total'] + $r['valor_em_aberto']);
        $porCliente[$r['cliente']]['quantidade']++;
        $porCliente[$r['cliente']]['maior_atraso_dias'] = max($porCliente[$r['cliente']]['maior_atraso_dias'], $r['dias_atraso'] ?? 0);
    }
    usort($porCliente, fn($a, $b) => $b['total'] <=> $a['total']);

    $observacoes = [];
    if ($aviso) {
        $observacoes[] = $aviso;
    }
    return [
        'data_referencia' => fx_hoje(),
        'total'           => fx_somar($vencidas, 'valor_em_aberto'),
        'quantidade'      => count($vencidas),
        'por_cliente'     => array_values($porCliente),
        'cobrancas'       => $vencidas,
        'observacoes'     => $observacoes,
    ];
}

/**
 * Quanto sobra para aplicar no mês, em duas leituras:
 * - conservadora: só o dinheiro que já entrou (recebido − pago) menos tudo
 *   que ainda falta pagar no mês e as despesas atrasadas de meses
 *   anteriores;
 * - projetada: o saldo projetado do mês (supõe que toda a receita prevista
 *   entra) menos as despesas atrasadas de meses anteriores.
 */
function fx_saldo_para_investir(string $mes, ?float $valorPretendido): array
{
    $resumo = fx_resumo_mes($mes);
    [$ini] = fx_limites_mes($mes);
    $atrasadasAnteriores = fx_despesas('2000-01-01', date('Y-m-d', strtotime("$ini -1 day")), true);
    $totalAtrasadasAnteriores = fx_somar($atrasadasAnteriores, 'valor');

    $compromissos = fx_r($resumo['despesa']['em_aberto'] + $totalAtrasadasAnteriores);
    $conservador = fx_r($resumo['receita']['recebida'] - $resumo['despesa']['paga'] - $compromissos);
    $projetado = fx_r($resumo['saldo_projetado'] - $totalAtrasadasAnteriores);

    $resultado = [
        'mes'                                  => $mes,
        'disponivel_conservador'               => $conservador,
        'disponivel_projetado'                 => $projetado,
        'receita_recebida'                     => $resumo['receita']['recebida'],
        'receita_ainda_a_receber'              => fx_r($resumo['receita']['a_receber'] + $resumo['receita']['vencida_nao_paga']),
        'receita_vencida_nao_paga'             => $resumo['receita']['vencida_nao_paga'],
        'despesa_paga'                         => $resumo['despesa']['paga'],
        'despesa_em_aberto_no_mes'             => $resumo['despesa']['em_aberto'],
        'despesas_atrasadas_meses_anteriores'  => $totalAtrasadasAnteriores,
        'saldo_projetado_do_mes'               => $resumo['saldo_projetado'],
        'como_ler' => [
            'disponivel_conservador' => 'Dinheiro que já entrou no mês menos o que já foi pago, menos tudo que ainda falta pagar no mês e as despesas atrasadas de meses anteriores. Não conta com receita que ainda não entrou.',
            'disponivel_projetado'   => 'Saldo projetado do mês (toda a receita prevista entrando) menos as despesas atrasadas de meses anteriores. Depende de os clientes pagarem em dia.',
        ],
        'observacoes' => array_merge(
            ['O CRM não tem saldo bancário: sobras de meses anteriores não entram na conta. Confira o extrato antes de aplicar.'],
            array_slice($resumo['observacoes'], 0, -1)
        ),
    ];

    if ($valorPretendido !== null) {
        $resultado['valor_pretendido'] = fx_r($valorPretendido);
        $resultado['cabe_no_conservador'] = $valorPretendido <= $conservador;
        $resultado['cabe_no_projetado'] = $valorPretendido <= $projetado;
    }
    return $resultado;
}
