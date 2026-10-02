<?php
/**
 * Cálculo da prévia do fechamento de consultores (contas a pagar
 * semi-automático — ver docs/fechamento-consultores-crm.md). Só calcula
 * e devolve: nada é gravado aqui. Quem lança é o
 * lancar-fechamento-consultores.php, depois que o Felipe revisa a prévia
 * na tela (crm-newsiga-fechamento-consultores.php).
 *
 * Devolve uma linha por contrato de fornecedor ativo, com o valor
 * sugerido pra competência:
 *
 *   - mensalidade_fixa: o valor do contrato. As horas do consultor
 *     aparecem só como informação.
 *   - hora_aberta de fornecedor vinculado ao Movidesk
 *     (fornecedores.movidesk_technician_name): horas apontadas × R$/h.
 *   - hora_aberta sem horas no Movidesk (fornecedor sem vínculo, ou
 *     contrato de um cliente que não tem empresa no Movidesk — ex:
 *     HubVision/MobCode, cujas horas vêm do sistema do parceiro): linha
 *     manual, o valor é digitado na tela.
 *
 * Pra qual contrato vai cada hora apontada (o cliente do contrato é
 * casado com a organização do ticket por clientes.movidesk_organization):
 *
 *   1. Cliente com contrato FIXO desse fornecedor: hora coberta pelo
 *      fixo, não é paga por hora (ex: Robson na Ocaporã e na Becker).
 *   2. Cliente com contrato hora_aberta próprio: taxa desse contrato
 *      (ex: Robson na Tron, R$ 50/h).
 *   3. Qualquer outro cliente, inclusive demanda interna (Financeiro,
 *      Contabilidade...): contrato hora_aberta sem cliente, a "regra
 *      geral" do fornecedor — toda hora apontada é paga.
 *   4. Fornecedor só com fixo sem cliente (ex: Normando, Luis Felipe):
 *      todas as horas ficam como informação nesse fixo.
 */

require_once __DIR__ . '/movidesk-client.php';

function fechamento_normalizar_nome(?string $nome): string
{
    return mb_strtolower(trim((string) $nome), 'UTF-8');
}

/** Vencimento no mês seguinte ao da competência, no dia do contrato — mesma regra do lançamento manual */
function fechamento_vencimento(string $competencia, int $diaVencimento): string
{
    $mesSeguinteTs = strtotime($competencia . '-01 +1 month');
    $dia = min(max($diaVencimento, 1), (int) date('t', $mesSeguinteTs));
    return date('Y-m-', $mesSeguinteTs) . sprintf('%02d', $dia);
}

/**
 * @return array{competencia: string, linhas: array<int, array<string, mixed>>, avisos: string[], consultores_sem_fornecedor: array<int, array{nome: string, horas: float}>}
 */
function calcular_previa_fechamento_consultores(PDO $db, string $competencia): array
{
    $contratos = $db->query("
        SELECT
            cf.id, cf.fornecedor_id, cf.cliente_id, cf.tipo, cf.valor, cf.valor_hora, cf.dia_vencimento, cf.descricao,
            f.nome AS fornecedor_nome, f.categoria AS fornecedor_categoria, f.movidesk_technician_name,
            cl.nome AS cliente_nome, cl.movidesk_organization
        FROM contratos_fornecedor cf
        JOIN fornecedores f ON f.id = cf.fornecedor_id
        LEFT JOIN clientes cl ON cl.id = cf.cliente_id
        WHERE cf.status = 'ativo' AND f.status = 'ativo'
        ORDER BY f.nome, cf.id
    ")->fetchAll();

    $stmtExistentes = $db->prepare('SELECT id, contrato_fornecedor_id, valor, status FROM despesas_competencia WHERE competencia = ?');
    $stmtExistentes->execute([$competencia]);
    $existentes = [];
    foreach ($stmtExistentes->fetchAll() as $despesa) {
        $existentes[$despesa['contrato_fornecedor_id']] = [
            'id' => (int) $despesa['id'],
            'valor' => (float) $despesa['valor'],
            'status' => $despesa['status'],
        ];
    }

    $horasPorConsultor = [];
    foreach (movidesk_horas_por_consultor($competencia) as $consultor => $clientes) {
        $horasPorConsultor[fechamento_normalizar_nome($consultor)] = ['nome' => $consultor, 'clientes' => $clientes];
    }

    $linhas = [];
    $porFornecedor = [];
    foreach ($contratos as $c) {
        $linhas[$c['id']] = [
            'contrato_fornecedor_id' => (int) $c['id'],
            'fornecedor_id' => (int) $c['fornecedor_id'],
            'fornecedor_nome' => $c['fornecedor_nome'],
            'fornecedor_categoria' => $c['fornecedor_categoria'],
            'descricao' => $c['descricao'],
            'tipo' => $c['tipo'],
            'cliente_nome' => $c['cliente_nome'],
            'valor_hora' => $c['valor_hora'] !== null ? (float) $c['valor_hora'] : null,
            'calculo' => 'manual', // 'fixo' | 'movidesk' | 'manual'
            'horas' => null,
            'horas_por_cliente' => [],
            'valor_base' => null,
            'vencimento' => fechamento_vencimento($competencia, (int) $c['dia_vencimento']),
            'despesa_existente' => $existentes[$c['id']] ?? null,
            'aviso' => null,
        ];
        $porFornecedor[$c['fornecedor_id']][] = $c;
    }

    $avisos = [];
    $consultoresUsados = [];

    foreach ($porFornecedor as $contratosDoFornecedor) {
        $nomeFornecedor = $contratosDoFornecedor[0]['fornecedor_nome'];
        $consultor = fechamento_normalizar_nome($contratosDoFornecedor[0]['movidesk_technician_name']);
        $temVinculo = $consultor !== '';

        $fixoPorOrganizacao = [];  // organização => id do contrato fixo que cobre as horas dela
        $taxaPorOrganizacao = [];  // organização => id do contrato hora_aberta específico dela
        $regraGeral = [];          // ids dos contratos hora_aberta sem cliente
        $fixosGerais = [];         // ids dos contratos fixos sem cliente

        foreach ($contratosDoFornecedor as $c) {
            $id = $c['id'];
            $organizacao = $c['cliente_id'] ? fechamento_normalizar_nome($c['movidesk_organization']) : '';

            if ($c['tipo'] === 'mensalidade_fixa') {
                $linhas[$id]['calculo'] = 'fixo';
                $linhas[$id]['valor_base'] = (float) $c['valor'];
                if ($organizacao !== '') {
                    $fixoPorOrganizacao[$organizacao] = $id;
                } elseif (!$c['cliente_id']) {
                    $fixosGerais[] = $id;
                }
                continue;
            }

            if (!$temVinculo) {
                continue; // fornecedor sem consultor no Movidesk — valor digitado na tela
            }
            if (!$c['cliente_id']) {
                $regraGeral[] = $id;
            } elseif ($organizacao !== '') {
                $taxaPorOrganizacao[$organizacao] = $id;
                $linhas[$id]['calculo'] = 'movidesk';
            }
            // contrato de cliente sem empresa no Movidesk: fica manual
        }

        if (count($regraGeral) === 1) {
            $linhas[$regraGeral[0]]['calculo'] = 'movidesk';
        } elseif (count($regraGeral) > 1) {
            // Sem saber qual é a regra geral, não dá pra distribuir as
            // horas — melhor deixar tudo manual do que escolher uma taxa.
            foreach ($regraGeral as $id) {
                $linhas[$id]['aviso'] = 'Mais de um contrato por hora sem cliente pra este fornecedor — vincule o cliente nos contratos específicos pra sobrar uma só regra geral.';
            }
            $avisos[] = "$nomeFornecedor: mais de um contrato por hora sem cliente vinculado — as horas que não são de um cliente com contrato próprio não foram calculadas.";
        }

        if (!$temVinculo || !isset($horasPorConsultor[$consultor])) {
            continue;
        }
        $consultoresUsados[$consultor] = true;

        foreach ($horasPorConsultor[$consultor]['clientes'] as $cliente => $horas) {
            $organizacao = fechamento_normalizar_nome($cliente);
            if (isset($fixoPorOrganizacao[$organizacao])) {
                $linhas[$fixoPorOrganizacao[$organizacao]]['horas_por_cliente'][] = ['cliente' => $cliente, 'horas' => round($horas, 4), 'pago' => false];
            } elseif (isset($taxaPorOrganizacao[$organizacao])) {
                $linhas[$taxaPorOrganizacao[$organizacao]]['horas_por_cliente'][] = ['cliente' => $cliente, 'horas' => round($horas, 4), 'pago' => true];
            } elseif (count($regraGeral) === 1) {
                $linhas[$regraGeral[0]]['horas_por_cliente'][] = ['cliente' => $cliente, 'horas' => round($horas, 4), 'pago' => true];
            } elseif (count($regraGeral) === 0 && $fixosGerais) {
                $linhas[$fixosGerais[0]]['horas_por_cliente'][] = ['cliente' => $cliente, 'horas' => round($horas, 4), 'pago' => false];
            } elseif (count($regraGeral) === 0) {
                $avisos[] = "$nomeFornecedor: " . number_format($horas, 2, ',', '.') . "h em \"$cliente\" sem contrato que se aplique — confira os contratos do fornecedor.";
            }
        }
    }

    foreach ($linhas as &$linha) {
        usort($linha['horas_por_cliente'], static fn($a, $b) => $b['horas'] <=> $a['horas']);
        $total = array_sum(array_column($linha['horas_por_cliente'], 'horas'));

        if ($linha['calculo'] === 'movidesk') {
            // Soma com a precisão completa e arredonda só o valor final,
            // pra não perder centavos (13h32min = 13,5333h, não 13,53h).
            $linha['horas'] = round($total, 4);
            $linha['valor_base'] = round($total * (float) $linha['valor_hora'], 2);
        } elseif ($linha['horas_por_cliente']) {
            $linha['horas'] = round($total, 4);
        }
    }
    unset($linha);

    $semFornecedor = [];
    foreach ($horasPorConsultor as $chave => $dados) {
        if (!isset($consultoresUsados[$chave])) {
            $semFornecedor[] = ['nome' => $dados['nome'], 'horas' => round(array_sum($dados['clientes']), 4)];
        }
    }

    return [
        'competencia' => $competencia,
        'linhas' => array_values($linhas),
        'avisos' => $avisos,
        'consultores_sem_fornecedor' => $semFornecedor,
    ];
}
