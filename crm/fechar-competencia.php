<?php
/**
 * Fechamento mensal — roda uma vez por competência (idealmente via cron,
 * todo dia 1º) e gera fatura + cobrança ASAAS pros contratos recorrentes
 * E a próxima parcela pendente de cada projeto parcelado.
 *
 * Cobre os 4 tipos recorrentes:
 *   - mensalidade_fixa: valor cheio, sempre.
 *   - banco_horas_minimo: max(mínimo garantido, consumo × valor_hora) —
 *     se não tiver valor_hora (sem excedente) ou não tiver consumo
 *     disponível, cobra só o mínimo.
 *   - hora_aberta: consumo × valor_hora. Se o contrato usa taxa
 *     diferenciada por tipo de hora no Movidesk (ex: Mari Louças), calcula
 *     automático via movidesk_valor_diferenciado() — franquia e taxas por
 *     tipo de hora vêm direto do Movidesk, sem duplicar nada localmente.
 *   - banco_horas_consumo: min(consumo, horas_banco) × valor_hora, mais
 *     o que passar do banco × valor_hora_excedente. Mesmo tratamento de
 *     taxa diferenciada do hora_aberta, acima.
 *
 * IMPOSTO: contratos baseados em horas (banco_horas_minimo, hora_aberta,
 * banco_horas_consumo) levam 15% de imposto "por fora" — o valor calculado
 * pelas horas é o líquido, a fatura sai maior (líquido / 0,85). Contratos
 * de mensalidade_fixa e projeto_parcelado NÃO levam esse ajuste — o valor
 * cadastrado já é o valor final, com imposto embutido.
 *
 * Contratos sem `movidesk_contract_name` configurado (ou sem a
 * integração Movidesk disponível) são pulados com aviso, nunca geram
 * cobrança errada por falta de dado.
 *
 * PROJETO PARCELADO: tratado numa seção separada, no fim do script —
 * gera só a PRÓXIMA parcela pendente (menor número ainda 'a_gerar') de
 * cada projeto ativo, uma por rodada, nunca todas de uma vez. Rodando
 * mensalmente, a sequência avança sozinha no ritmo certo, sem mandar
 * cobrança futura pro cliente antes da hora. Não passa pela tabela
 * `faturas` nem pelo imposto por fora — usa o valor já calculado de
 * cada parcela (dividirValorEmParcelas, no cadastro do contrato).
 *
 * Uso: rode via linha de comando (cron) —
 *   php fechar-competencia.php 2026-09
 * Sem o argumento, fecha o MÊS ANTERIOR ao atual (é o mês que acabou de
 * terminar — faz sentido pro cron rodar à meia-noite do dia 1º e fechar
 * o mês que passou, não o que está começando). Também aceita chamada
 * via navegador com ?competencia=2026-09&token=SEU_TOKEN (protegido).
 *
 * FECHAMENTO DE UM CONTRATO SÓ (útil pra rodar cliente a cliente, com
 * mais controle manual): passe também o id do contrato —
 *   CLI:      php fechar-competencia.php 2026-09 11
 *   Navegador: ?competencia=2026-09&contrato_id=11&token=SEU_TOKEN
 * Sem esse parâmetro, roda todos os contratos elegíveis normalmente
 * (comportamento de sempre, usado pelo cron).
 */

require_once __DIR__ . '/asaas-client.php';
require_once __DIR__ . '/movidesk-client.php';

$competenciaPadrao = date('Y-m', strtotime('first day of last month'));

$ehCli = (php_sapi_name() === 'cli');

if ($ehCli) {
    $competencia = $argv[1] ?? $competenciaPadrao;
    $contratoIdFiltro = isset($argv[2]) ? (int) $argv[2] : null;
} else {
    // Chamada via navegador/curl externo — exige o mesmo token do webhook
    // como proteção simples, já que esse script cria cobrança de verdade.
    $tokenRecebido = $_GET['token'] ?? '';
    if (!defined('ASAAS_WEBHOOK_TOKEN') || !hash_equals(ASAAS_WEBHOOK_TOKEN, $tokenRecebido)) {
        http_response_code(401);
        echo "Token inválido.\n";
        exit;
    }
    header('Content-Type: text/plain; charset=utf-8');
    $competencia = $_GET['competencia'] ?? $competenciaPadrao;
    $contratoIdFiltro = isset($_GET['contrato_id']) ? (int) $_GET['contrato_id'] : null;
}

if (!preg_match('/^\d{4}-\d{2}$/', $competencia)) {
    echo "Competência inválida: $competencia (formato esperado YYYY-MM)\n";
    exit(1);
}

function montarDataVencimento(string $competencia, int $diaVencimento): string
{
    // O vencimento cai no mês SEGUINTE ao da competência — cobra em
    // setembro o que foi consumido/prestado em agosto, no dia configurado
    // no contrato. (Antes calculava dentro do próprio mês da competência,
    // que na prática está sempre no passado quando o fechamento roda —
    // sempre rodamos depois que o mês fechou — fazendo cair sempre na
    // regra de segurança abaixo e virar "hoje" pra tudo, sem exceção.)
    $mesSeguinteTs = strtotime($competencia . '-01 +1 month');
    $anoVenc = (int) date('Y', $mesSeguinteTs);
    $mesVenc = (int) date('m', $mesSeguinteTs);
    $ultimoDia = (int) date('t', $mesSeguinteTs);
    $dia = min($diaVencimento, $ultimoDia); // protege dia 31 em mês de 30
    $vencimento = sprintf('%04d-%02d-%02d', $anoVenc, $mesVenc, $dia);

    // Rede de segurança, agora um caso raro de verdade (fechamento rodando
    // com bastante atraso): se mesmo assim já passou, empurra pra hoje.
    $hoje = date('Y-m-d');
    if ($vencimento < $hoje) {
        return $hoje;
    }
    return $vencimento;
}

/**
 * Alíquota do imposto aplicado "por fora" sobre contratos baseados em
 * horas (banco_horas_minimo, hora_aberta, banco_horas_consumo). "Por
 * fora" significa que o valor calculado pelas horas é o valor LÍQUIDO
 * que a Newsiga deve receber — o valor da fatura é maior, de forma que
 * o imposto sobre o valor DA FATURA (não sobre o valor líquido) feche
 * exatamente com a diferença. Fórmula: fatura = líquido / (1 - alíquota).
 *
 * Não se aplica a mensalidade_fixa (Becker, Tron, hotéis, etc.) nem a
 * projeto_parcelado — nesses o valor cadastrado já é o valor final da
 * fatura, com imposto já embutido.
 */
const ALIQUOTA_IMPOSTO_HORAS = 0.15;

/** Aplica o imposto "por fora" (gross-up) sobre um valor líquido */
function aplicarImpostoPorFora(float $valorLiquido): float
{
    return round($valorLiquido / (1 - ALIQUOTA_IMPOSTO_HORAS), 2);
}

/** Calcula o valor LÍQUIDO (sem imposto) de um contrato baseado em horas, conforme o tipo */
function calcularValorLiquidoHoras(array $contrato, string $competencia): float
{
    switch ($contrato['tipo']) {
        case 'banco_horas_minimo':
            $minimo = (float) $contrato['valor'];
            if (!$contrato['valor_hora']) {
                // Sem excedente configurado: cobra só o mínimo, sempre.
                return $minimo;
            }
            if (!$contrato['movidesk_contract_name']) {
                throw new RuntimeException('sem vínculo com o Movidesk configurado (movidesk_contract_name)');
            }
            if (!$contrato['horas_minimas']) {
                // Tem excedente configurado, mas não sabemos onde ele começa —
                // calcular aqui seria repetir o erro que já corrigimos (cobrar
                // excedente sobre horas que já estão dentro do pacote).
                throw new RuntimeException('excedente configurado mas sem "quantidade de horas do pacote" definida — edite o contrato e preencha horas_minimas');
            }
            if (movidesk_usa_taxa_diferenciada($contrato['movidesk_contract_name'])) {
                // Combinação (mínimo garantido + taxa diferenciada por tipo de
                // hora) ainda não tem fórmula validada — continua bloqueando
                // esse caso específico, calcule manualmente.
                throw new RuntimeException('contrato usa taxa diferenciada por tipo de atividade no Movidesk, combinado com mínimo garantido — calcule manualmente (ver relatório da área do cliente)');
            }
            $horas = movidesk_consumo_cache((int) $contrato['contrato_id'], $contrato['movidesk_contract_name'], $competencia);
            $horasMinimas = (float) $contrato['horas_minimas'];
            if ($horas <= $horasMinimas) {
                return $minimo; // dentro do pacote — só o mínimo, como sempre
            }
            $horasExcedentes = $horas - $horasMinimas;
            return $minimo + ($horasExcedentes * (float) $contrato['valor_hora']);

        case 'hora_aberta':
            if (!$contrato['movidesk_contract_name']) {
                throw new RuntimeException('sem vínculo com o Movidesk configurado (movidesk_contract_name)');
            }
            if (movidesk_usa_taxa_diferenciada($contrato['movidesk_contract_name'])) {
                // Taxa diferenciada por tipo de hora (ex: Mari Louças) — as
                // franquias e taxas vêm direto do Movidesk (typeActivities),
                // mesmo motor já validado na área do cliente. Não usa
                // valor_hora local nenhum, o Movidesk é a fonte de verdade.
                return movidesk_valor_diferenciado($contrato['movidesk_contract_name'], $competencia);
            }
            $horas = movidesk_consumo_cache((int) $contrato['contrato_id'], $contrato['movidesk_contract_name'], $competencia);
            return $horas * (float) $contrato['valor_hora'];

        case 'banco_horas_consumo':
            if (!$contrato['movidesk_contract_name']) {
                throw new RuntimeException('sem vínculo com o Movidesk configurado (movidesk_contract_name)');
            }
            if (movidesk_usa_taxa_diferenciada($contrato['movidesk_contract_name'])) {
                return movidesk_valor_diferenciado($contrato['movidesk_contract_name'], $competencia);
            }
            $horas = movidesk_consumo_cache((int) $contrato['contrato_id'], $contrato['movidesk_contract_name'], $competencia);
            $horasBanco = (float) $contrato['horas_banco'];
            $dentroDoBanco = min($horas, $horasBanco);
            $excedente = max(0, $horas - $horasBanco);
            return ($dentroDoBanco * (float) $contrato['valor_hora']) + ($excedente * (float) $contrato['valor_hora_excedente']);
    }
    return 0.0;
}

/**
 * Calcula o valor final a faturar de um contrato nessa competência.
 * Devolve os três componentes — líquido, imposto e total — pra gravar
 * a composição na fatura, não só o resultado final.
 *
 * mensalidade_fixa: valor cadastrado direto, já inclui imposto (líquido
 * = total, imposto = 0). Demais tipos baseados em horas: calcula o
 * valor líquido (mesma lógica de sempre) e aplica o imposto por fora
 * (15%) por cima.
 *
 * @return array{liquido: float, imposto: float, total: float}
 */
function calcularValorContrato(array $contrato, string $competencia): array
{
    if ($contrato['tipo'] === 'mensalidade_fixa') {
        $valor = (float) $contrato['valor'];
        return ['liquido' => $valor, 'imposto' => 0.0, 'total' => $valor];
    }
    $valorLiquido = calcularValorLiquidoHoras($contrato, $competencia);
    if ($valorLiquido <= 0) {
        // preserva o comportamento de "pular, sem consumo" — não faz
        // sentido aplicar imposto sobre zero
        return ['liquido' => 0.0, 'imposto' => 0.0, 'total' => 0.0];
    }
    $valorTotal = aplicarImpostoPorFora($valorLiquido);
    return [
        'liquido' => $valorLiquido,
        'imposto' => round($valorTotal - $valorLiquido, 2),
        'total' => $valorTotal,
    ];
}

$db = getDb();
$asaas = new AsaasClient();

echo "=== Fechamento da competência $competencia" . ($contratoIdFiltro ? " — contrato #$contratoIdFiltro" : "") . " ===\n";

$sql = "
    SELECT c.id AS contrato_id, c.tipo, c.valor, c.valor_hora, c.valor_hora_excedente,
           c.horas_banco, c.horas_minimas, c.movidesk_contract_name, c.dia_vencimento,
           c.primeira_competencia_sistema, c.descricao_servico, cl.nome, cl.asaas_customer_id
    FROM contratos c
    JOIN clientes cl ON cl.id = c.cliente_id
    WHERE c.status = 'ativo'
      AND c.faturamento_gerenciado_por = 'sistema'
      AND c.tipo IN ('mensalidade_fixa', 'banco_horas_minimo', 'hora_aberta', 'banco_horas_consumo')
";
$parametros = [];
if ($contratoIdFiltro) {
    $sql .= " AND c.id = ?";
    $parametros[] = $contratoIdFiltro;
}
$stmt = $db->prepare($sql);
$stmt->execute($parametros);
$contratos = $stmt->fetchAll();

if ($contratoIdFiltro && count($contratos) === 0) {
    echo "Nenhum contrato recorrente elegível encontrado com id=$contratoIdFiltro (pode ser um projeto parcelado — confira a seção abaixo).\n";
}

echo count($contratos) . " contrato(s) elegível(is) encontrado(s).\n\n";

$gerados = 0;
$pulados = 0;
$erros = 0;

foreach ($contratos as $contrato) {
    $nome = $contrato['nome'];

    // Protege a transição de assinatura ASAAS suspensa
    if ($contrato['primeira_competencia_sistema'] && $competencia < $contrato['primeira_competencia_sistema']) {
        echo "- $nome: pulado (antes da primeira competência do sistema)\n";
        $pulados++;
        continue;
    }

    if (!$contrato['asaas_customer_id']) {
        echo "- $nome: PULADO — sem asaas_customer_id (contrato ativo mas nunca confirmado no ASAAS?)\n";
        $pulados++;
        continue;
    }

    // Idempotência de verdade: só pula se já existe fatura E ela já foi
    // gerada com sucesso (status diferente de 'a_gerar'). Se uma tentativa
    // anterior falhou no meio do caminho (ex: vencimento no passado), a
    // fatura fica travada em 'a_gerar' — nesse caso, reaproveita a mesma
    // fatura e tenta de novo, em vez de fingir que já está resolvida.
    $check = $db->prepare('SELECT id, status FROM faturas WHERE contrato_id = ? AND competencia = ?');
    $check->execute([$contrato['contrato_id'], $competencia]);
    $faturaExistente = $check->fetch();

    if ($faturaExistente && $faturaExistente['status'] !== 'a_gerar') {
        echo "- $nome: pulado (fatura já existe e já foi gerada)\n";
        $pulados++;
        continue;
    }

    try {
        $calculo = calcularValorContrato($contrato, $competencia);
    } catch (Throwable $e) {
        echo "- $nome: PULADO — " . $e->getMessage() . "\n";
        $pulados++;
        continue;
    }

    $valor = $calculo['total'];
    $valorLiquido = $calculo['liquido'];
    $valorImposto = $calculo['imposto'];

    if ($valor <= 0) {
        echo "- $nome: PULADO — valor calculado é zero (sem consumo na competência)\n";
        $pulados++;
        continue;
    }

    $vencimento = montarDataVencimento($competencia, (int) $contrato['dia_vencimento']);

    if ($faturaExistente) {
        // Reaproveita a fatura travada de uma tentativa anterior, já
        // atualizando valor/vencimento (podem ter mudado desde então).
        $faturaId = $faturaExistente['id'];
        $db->prepare('UPDATE faturas SET valor = ?, valor_liquido = ?, valor_imposto = ?, vencimento = ? WHERE id = ?')
           ->execute([$valor, $valorLiquido, $valorImposto, $vencimento, $faturaId]);
    } else {
        // Cria a fatura como 'a_gerar' ANTES de chamar o ASAAS — se a
        // chamada falhar, ela fica visível como pendente em vez de sumir
        // silenciosamente (e vira candidata a retry na próxima rodada).
        $insert = $db->prepare("INSERT INTO faturas (contrato_id, competencia, valor, valor_liquido, valor_imposto, vencimento, status) VALUES (?, ?, ?, ?, ?, ?, 'a_gerar')");
        $insert->execute([$contrato['contrato_id'], $competencia, $valor, $valorLiquido, $valorImposto, $vencimento]);
        $faturaId = $db->lastInsertId();
    }

    $descricaoCobranca = $contrato['descricao_servico'] ?: "Newsiga — $nome — competência $competencia";

    try {
        $cobranca = $asaas->criarCobranca(
            $contrato['asaas_customer_id'],
            $valor,
            $vencimento,
            "fatura:$faturaId",
            $descricaoCobranca
        );
        $db->prepare("UPDATE faturas SET status = 'gerado', asaas_payment_id = ? WHERE id = ?")
           ->execute([$cobranca['id'], $faturaId]);

        $composicao = $valorImposto > 0
            ? " (líquido R$ " . number_format($valorLiquido, 2, ',', '.') . " + imposto R$ " . number_format($valorImposto, 2, ',', '.') . ")"
            : "";
        echo "- $nome: GERADO — R$ " . number_format($valor, 2, ',', '.') . "$composicao venc. $vencimento\n";
        $gerados++;
    } catch (Throwable $e) {
        echo "- $nome: ERRO ao criar cobrança — " . $e->getMessage() . " (fatura #$faturaId ficou como 'a_gerar', não perdida)\n";
        error_log("Falha ao gerar cobrança da fatura $faturaId: " . $e->getMessage());
        $erros++;
    }
}

echo "\n=== Resumo (recorrentes): $gerados gerada(s), $pulados pulada(s), $erros erro(s) ===\n";

// ---------------------------------------------------------------------
// Projetos parcelados — gera só a PRÓXIMA parcela pendente de cada
// contrato, uma por rodada, nunca todas de uma vez. Rodando o
// fechamento todo mês, a sequência avança sozinha, uma parcela por vez,
// no ritmo certo — sem mandar cobrança futura antes da hora.
// ---------------------------------------------------------------------
echo "\n=== Projetos parcelados" . ($contratoIdFiltro ? " — contrato #$contratoIdFiltro" : "") . " ===\n";

$sqlProjetos = "
    SELECT c.id AS contrato_id, c.descricao_servico, cl.nome, cl.asaas_customer_id
    FROM contratos c
    JOIN clientes cl ON cl.id = c.cliente_id
    WHERE c.status = 'ativo'
      AND c.faturamento_gerenciado_por = 'sistema'
      AND c.tipo = 'projeto_parcelado'
";
$parametrosProjetos = [];
if ($contratoIdFiltro) {
    $sqlProjetos .= " AND c.id = ?";
    $parametrosProjetos[] = $contratoIdFiltro;
}
$stmtProjetos = $db->prepare($sqlProjetos);
$stmtProjetos->execute($parametrosProjetos);
$projetos = $stmtProjetos->fetchAll();

echo count($projetos) . " projeto(s) elegível(is) encontrado(s).\n\n";

$geradosProjetos = 0;
$puladosProjetos = 0;
$errosProjetos = 0;

foreach ($projetos as $projeto) {
    $nomeProjeto = $projeto['nome'];

    if (!$projeto['asaas_customer_id']) {
        echo "- $nomeProjeto (contrato #{$projeto['contrato_id']}): PULADO — sem asaas_customer_id\n";
        $puladosProjetos++;
        continue;
    }

    $stmtParcela = $db->prepare("
        SELECT id, numero, valor, vencimento
        FROM parcelas
        WHERE contrato_id = ? AND status = 'a_gerar' AND asaas_payment_id IS NULL
        ORDER BY numero ASC
        LIMIT 1
    ");
    $stmtParcela->execute([$projeto['contrato_id']]);
    $parcela = $stmtParcela->fetch();

    if (!$parcela) {
        echo "- $nomeProjeto (contrato #{$projeto['contrato_id']}): pulado (sem parcela pendente — projeto quitado ou já gerada)\n";
        $puladosProjetos++;
        continue;
    }

    // Só gera se o vencimento cair dentro do mês atual (ou já tiver
    // passado, caso o fechamento tenha ficado um tempo sem rodar) —
    // nunca gera cobrança de um mês futuro adiantado. Rodando o
    // fechamento no dia 1º, a parcela sai com dias de antecedência do
    // próprio vencimento dela, nunca meses antes.
    $mesAtual = date('Y-m');
    $mesVencimentoParcela = substr($parcela['vencimento'], 0, 7);
    if ($mesVencimentoParcela > $mesAtual) {
        echo "- $nomeProjeto (contrato #{$projeto['contrato_id']}): pulado (parcela nº {$parcela['numero']} vence em {$parcela['vencimento']}, ainda não é a vez dela)\n";
        $puladosProjetos++;
        continue;
    }

    $descricaoParcela = $projeto['descricao_servico'] ?: "Newsiga — parcela {$parcela['numero']} — contrato #{$projeto['contrato_id']}";

    try {
        $cobranca = $asaas->criarCobranca(
            $projeto['asaas_customer_id'],
            (float) $parcela['valor'],
            $parcela['vencimento'],
            "parcela:{$parcela['id']}",
            $descricaoParcela
        );
        $db->prepare("UPDATE parcelas SET asaas_payment_id = ?, status = 'gerado' WHERE id = ?")
           ->execute([$cobranca['id'], $parcela['id']]);

        echo "- $nomeProjeto (contrato #{$projeto['contrato_id']}): GERADA parcela nº {$parcela['numero']} — R$ " . number_format((float) $parcela['valor'], 2, ',', '.') . " venc. {$parcela['vencimento']}\n";
        $geradosProjetos++;
    } catch (Throwable $e) {
        echo "- $nomeProjeto (contrato #{$projeto['contrato_id']}): ERRO ao criar cobrança da parcela nº {$parcela['numero']} — " . $e->getMessage() . "\n";
        error_log("Falha ao gerar cobrança da parcela {$parcela['id']}: " . $e->getMessage());
        $errosProjetos++;
    }
}

echo "\n=== Resumo (projetos parcelados): $geradosProjetos gerada(s), $puladosProjetos pulada(s), $errosProjetos erro(s) ===\n";
