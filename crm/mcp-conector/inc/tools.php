<?php
/**
 * Definição das tools do conector MCP. O Claude escolhe qual chamar pela
 * descrição: por isso as descrições dizem em português o que cada uma
 * responde, quando usar e o que fica de fora.
 */

if (!defined('MCP_CONECTOR')) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/fluxo.php';

const MCP_ANOTACOES_LEITURA = [
    'readOnlyHint'    => true,
    'destructiveHint' => false,
    'idempotentHint'  => true,
    'openWorldHint'   => false,
];

function mcp_tools_definicoes(): array
{
    $mes = [
        'type'        => 'string',
        'pattern'     => '^\\d{4}-(0[1-9]|1[0-2])$',
        'description' => 'Mês no formato AAAA-MM (ex.: 2026-11). Se omitido, usa o mês atual.',
    ];
    $ateData = [
        'type'        => 'string',
        'pattern'     => '^\\d{4}-\\d{2}-\\d{2}$',
        'description' => 'Data limite de vencimento no formato AAAA-MM-DD. Se omitida, usa o último dia do mês atual.',
    ];

    return [
        [
            'name'        => 'resumo_mes',
            'title'       => 'Resumo do fluxo de caixa do mês',
            'description' => 'Resumo do fluxo de caixa da Newsiga em um mês: receita prevista, receita já recebida, despesas previstas, '
                . 'despesas já pagas, saldo projetado (receita prevista − despesa prevista) e saldo realizado (recebido − pago), '
                . 'com a lista de cada receita e despesa. Use para perguntas como "como está o caixa deste mês?", '
                . '"quanto entrou em outubro?" ou "quanto vou gastar em dezembro?". O mês de cada lançamento é o do vencimento. '
                . 'Valores em reais. O CRM não tem saldo bancário: o saldo é só o resultado do mês.',
            'inputSchema' => ['type' => 'object', 'properties' => ['mes' => $mes], 'additionalProperties' => false],
            'annotations' => MCP_ANOTACOES_LEITURA,
        ],
        [
            'name'        => 'despesas_a_vencer',
            'title'       => 'Despesas em aberto',
            'description' => 'Lista as despesas ainda não pagas com vencimento até uma data: fornecedor, categoria, valor, vencimento e '
                . 'situação (vencida, a pagar ou prevista sem lançamento). Inclui despesas atrasadas de meses anteriores e os contratos '
                . 'fixos de fornecedor que ainda não foram lançados no CRM. Use para "o que tenho para pagar até o fim do mês?" ou '
                . '"tem conta atrasada?". Valores em reais.',
            'inputSchema' => ['type' => 'object', 'properties' => ['ate_data' => $ateData], 'additionalProperties' => false],
            'annotations' => MCP_ANOTACOES_LEITURA,
        ],
        [
            'name'        => 'receitas_a_receber',
            'title'       => 'Receitas a receber',
            'description' => 'Lista as cobranças em aberto por cliente com vencimento até uma data: valor em aberto, vencimento, status da '
                . 'cobrança no ASAAS (consultado na hora) e situação (a receber, vencido, parcialmente recebido ou previsto). Inclui '
                . 'cobranças vencidas de meses anteriores e as mensalidades fixas cuja fatura ainda não foi gerada. Use para '
                . '"quanto ainda vai entrar este mês?" ou "quem ainda não pagou?". Valores em reais.',
            'inputSchema' => ['type' => 'object', 'properties' => ['ate_data' => $ateData], 'additionalProperties' => false],
            'annotations' => ['openWorldHint' => true] + MCP_ANOTACOES_LEITURA,
        ],
        [
            'name'        => 'saldo_disponivel_para_investir',
            'title'       => 'Saldo disponível para aplicar',
            'description' => 'Calcula quanto do resultado do mês pode ser aplicado, em duas leituras: conservadora (só o dinheiro que já '
                . 'entrou, descontando tudo que ainda falta pagar no mês e as despesas atrasadas) e projetada (contando que toda a '
                . 'receita prevista do mês entre). Use para "posso aplicar R$ X?" ou "quanto sobra este mês?"; informe o valor em '
                . '"valor" para receber a resposta direta. Não considera saldo bancário de meses anteriores, que o CRM não tem. '
                . 'Valores em reais.',
            'inputSchema' => [
                'type'                 => 'object',
                'properties'           => [
                    'mes'   => $mes,
                    'valor' => ['type' => 'number', 'minimum' => 0, 'description' => 'Valor em reais que se pretende aplicar (opcional).'],
                ],
                'additionalProperties' => false,
            ],
            'annotations' => ['openWorldHint' => true] + MCP_ANOTACOES_LEITURA,
        ],
        [
            'name'        => 'inadimplencia',
            'title'       => 'Inadimplência',
            'description' => 'Cobranças de clientes vencidas e não pagas até ontem, com o total por cliente, a quantidade e o maior atraso '
                . 'em dias, além da lista de cada cobrança com o status no ASAAS. Use para "quem está devendo?" ou "tenho '
                . 'inadimplência?". Valores em reais.',
            'inputSchema' => ['type' => 'object', 'properties' => new stdClass(), 'additionalProperties' => false],
            'annotations' => ['openWorldHint' => true] + MCP_ANOTACOES_LEITURA,
        ],
    ];
}

/** Executa a tool e devolve os dados estruturados. Erro de parâmetro sobe como InvalidArgumentException. */
function mcp_tools_executar(string $nome, array $argumentos): array
{
    $fimDoMes = date('Y-m-t');
    $permitidos = [
        'resumo_mes'                     => ['mes'],
        'despesas_a_vencer'              => ['ate_data'],
        'receitas_a_receber'             => ['ate_data'],
        'saldo_disponivel_para_investir' => ['mes', 'valor'],
        'inadimplencia'                  => [],
    ];
    if (!isset($permitidos[$nome])) {
        throw new DomainException("Tool desconhecida: $nome");
    }
    $extras = array_diff(array_keys($argumentos), $permitidos[$nome]);
    if ($extras) {
        throw new InvalidArgumentException('Parâmetro não aceito: ' . implode(', ', $extras) . '.');
    }

    switch ($nome) {
        case 'resumo_mes':
            return fx_resumo_mes(fx_validar_mes($argumentos['mes'] ?? null));

        case 'despesas_a_vencer':
            return fx_despesas_a_vencer(fx_limitar_data(fx_validar_data($argumentos['ate_data'] ?? null, $fimDoMes)));

        case 'receitas_a_receber':
            return fx_receitas_a_receber(fx_limitar_data(fx_validar_data($argumentos['ate_data'] ?? null, $fimDoMes)));

        case 'saldo_disponivel_para_investir':
            $valor = $argumentos['valor'] ?? null;
            if ($valor !== null && (!is_numeric($valor) || $valor < 0)) {
                throw new InvalidArgumentException('O parâmetro "valor" deve ser um número em reais, maior ou igual a zero.');
            }
            return fx_saldo_para_investir(fx_validar_mes($argumentos['mes'] ?? null), $valor === null ? null : (float) $valor);

        case 'inadimplencia':
            return fx_inadimplencia();
    }
    throw new DomainException("Tool desconhecida: $nome");
}

/** Limita a data a 12 meses à frente, para a consulta não crescer sem fim. */
function fx_limitar_data(string $data): string
{
    $limite = date('Y-m-t', strtotime('first day of +12 months'));
    if ($data > $limite) {
        throw new InvalidArgumentException("A data limite pode ser no máximo $limite (12 meses à frente).");
    }
    return $data;
}
