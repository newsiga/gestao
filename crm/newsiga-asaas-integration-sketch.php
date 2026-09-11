<?php
/**
 * ESBOÇO — Integração ASAAS (cobrança automática por contrato)
 * ----------------------------------------------------------------
 * Cobre três pontas:
 *   1. AsaasClient: criar cliente, criar cobrança, EDITAR cobrança (vencimento/valor)
 *   2. Rotina mensal de fechamento: contrato + consumo Movidesk -> cobrança no ASAAS
 *   3. Webhook receiver: mantém o status da fatura sincronizado sem ficar
 *      fazendo polling na API (o próprio ASAAS recomenda usar webhook)
 *
 * Assume as tabelas discutidas: clientes, contratos, parcelas, faturas.
 * Pontos com TODO são decisões que dependem do seu banco/estrutura real.
 */

// =========================================================
// CONFIGURAÇÃO
// =========================================================

define('ASAAS_API_KEY', getenv('ASAAS_API_KEY'));
define('ASAAS_BASE_URL', 'https://api.asaas.com/v3'); // homologação: https://sandbox.asaas.com/api/v3
define('ASAAS_WEBHOOK_TOKEN', getenv('ASAAS_WEBHOOK_TOKEN')); // authToken configurado no webhook do ASAAS


// =========================================================
// CLIENTE HTTP ASAAS
// =========================================================

class AsaasClient
{
    private function request(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init(ASAAS_BASE_URL . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'access_token: ' . ASAAS_API_KEY,
            ],
            CURLOPT_POSTFIELDS => $body ? json_encode($body) : null,
        ]);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true) ?? [];
        if ($status >= 400) {
            throw new RuntimeException("ASAAS $method $path falhou ($status): " . $response);
        }
        return $data;
    }

    /** Busca cliente existente no ASAAS pelo CNPJ/CPF — evita duplicar cadastro */
    public function buscarClientePorDocumento(string $cpfCnpj): ?array
    {
        $resultado = $this->request('GET', '/customers?cpfCnpj=' . urlencode($cpfCnpj));
        return $resultado['data'][0] ?? null; // API retorna lista paginada
    }

    /** Cria (ou reaproveita, se já existir) o cliente no ASAAS */
    public function criarOuBuscarCliente(string $nome, string $cpfCnpj, ?string $email = null): array
    {
        $existente = $this->buscarClientePorDocumento($cpfCnpj);
        if ($existente) {
            return $existente;
        }
        return $this->request('POST', '/customers', [
            'name' => $nome,
            'cpfCnpj' => $cpfCnpj,
            'email' => $email,
        ]);
    }

    /**
     * Cria uma cobrança avulsa vinculada a um cliente ASAAS.
     * $externalReference é o elo com o nosso banco — deixamos lá o id
     * interno da fatura, pra conciliar no webhook sem depender só do id do ASAAS.
     */
    public function criarCobranca(string $customerId, float $valor, string $vencimento, string $externalReference, string $descricao = ''): array
    {
        return $this->request('POST', '/payments', [
            'customer' => $customerId,
            'billingType' => 'UNDEFINED', // cliente escolhe boleto/Pix/cartão na fatura
            'value' => $valor,
            'dueDate' => $vencimento, // formato YYYY-MM-DD
            'description' => $descricao,
            'externalReference' => $externalReference,
        ]);
    }

    /**
     * Atualiza vencimento e/ou valor de uma cobrança já existente,
     * desde que ainda não tenha sido paga. É o que resolve o "cliente
     * pediu pra mudar a data" sem precisar cancelar e recriar.
     */
    public function atualizarCobranca(string $paymentId, array $campos): array
    {
        // campos aceitos aqui: value, dueDate, description etc.
        return $this->request('PUT', "/payments/$paymentId", $campos);
    }

    public function buscarCobranca(string $paymentId): array
    {
        return $this->request('GET', "/payments/$paymentId");
    }

    /**
     * Lista assinaturas (recorrência nativa do ASAAS) já existentes pra um cliente.
     * Essencial checar isso antes de ativar o fechamento automático de um
     * contrato — senão o mesmo mês pode ser cobrado duas vezes: uma pela
     * assinatura (que o ASAAS gera sozinho, até 40 dias antes do vencimento)
     * e outra pelo nosso fecharCompetencia().
     */
    public function listarAssinaturasDoCliente(string $customerId): array
    {
        $resultado = $this->request('GET', '/subscriptions?customer=' . urlencode($customerId));
        return $resultado['data'] ?? [];
    }

    /** Suspende uma assinatura no ASAAS — não gera mais cobrança nova, mas não apaga as já criadas */
    public function suspenderAssinatura(string $subscriptionId): array
    {
        return $this->request('PUT', "/subscriptions/$subscriptionId", ['status' => 'INACTIVE']);
    }

    /**
     * Atualiza o cadastro do cliente no ASAAS. Só é chamado depois que o
     * usuário resolveu manualmente uma divergência escolhendo "usar o dado local".
     */
    public function atualizarCliente(string $customerId, array $campos): array
    {
        return $this->request('PUT', "/customers/$customerId", $campos);
    }
}


// =========================================================
// ROTINA MENSAL DE FECHAMENTO (roda por cron, dia 1 ou dia de corte)
// =========================================================

function fecharCompetencia(PDO $db, AsaasClient $asaas, string $competencia): void
{
    // TODO: ajustar query pra sua estrutura real de tabelas
    // ATENÇÃO: filtra faturamento_gerenciado_por = 'sistema' — contratos
    // marcados como 'assinatura_asaas' são deliberadamente ignorados aqui,
    // porque o próprio ASAAS já gera a cobrança deles sozinho. Incluí-los
    // faria o cliente receber duas cobranças na mesma competência.
    $stmt = $db->prepare("
        SELECT c.id AS contrato_id, c.tipo, c.valor, c.valor_hora, c.horas_minimas,
               c.dia_vencimento, c.primeira_competencia_sistema,
               cl.id AS cliente_id, cl.nome, cl.asaas_customer_id
        FROM contratos c
        JOIN clientes cl ON cl.id = c.cliente_id
        WHERE c.status = 'ativo'
          AND c.tipo IN ('mensalidade_fixa', 'valor_hora', 'banco_horas_minimo')
          AND c.faturamento_gerenciado_por = 'sistema'
    ");
    $stmt->execute();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $contrato) {
        // Protege a transição de uma assinatura ASAAS suspensa: se o
        // contrato tem uma "primeira competência" marcada, o sistema nunca
        // gera fatura pra um mês anterior a ela — mesmo que já esteja como
        // 'sistema' — porque esse mês já foi coberto pela assinatura antiga.
        if ($contrato['primeira_competencia_sistema'] && $competencia < $contrato['primeira_competencia_sistema']) {
            continue;
        }

        $valorFatura = calcularValorFatura($contrato, $competencia);
        if ($valorFatura <= 0) {
            continue; // nada a faturar nesse contrato nessa competência
        }

        $vencimento = montarDataVencimento($competencia, (int) $contrato['dia_vencimento']);

        // Evita duplicar: já existe fatura dessa competência pra esse contrato?
        $existe = $db->prepare("SELECT id FROM faturas WHERE contrato_id = ? AND competencia = ?");
        $existe->execute([$contrato['contrato_id'], $competencia]);
        if ($existe->fetch()) {
            continue;
        }

        // Registra a fatura como pendente ANTES de chamar o ASAAS —
        // assim, se a chamada falhar, ela fica visível como "a gerar" no cockpit
        // em vez de sumir silenciosamente.
        $insert = $db->prepare("
            INSERT INTO faturas (contrato_id, competencia, valor, vencimento, status)
            VALUES (?, ?, ?, ?, 'a_gerar')
        ");
        $insert->execute([$contrato['contrato_id'], $competencia, $valorFatura, $vencimento]);
        $faturaId = $db->lastInsertId();

        try {
            $cobranca = $asaas->criarCobranca(
                $contrato['asaas_customer_id'],
                $valorFatura,
                $vencimento,
                "fatura:$faturaId", // externalReference — usado pra conciliar no webhook
                "Newsiga — {$contrato['nome']} — competência $competencia"
            );

            $db->prepare("UPDATE faturas SET status = 'gerado', asaas_payment_id = ? WHERE id = ?")
               ->execute([$cobranca['id'], $faturaId]);

        } catch (Throwable $e) {
            // fica como 'a_gerar' mesmo — o cockpit vai mostrar isso como pendente,
            // em vez de mascarar o erro
            error_log("Falha ao gerar cobrança da fatura $faturaId: " . $e->getMessage());
        }
    }
}

function calcularValorFatura(array $contrato, string $competencia): float
{
    switch ($contrato['tipo']) {
        case 'mensalidade_fixa':
            return (float) $contrato['valor'];

        case 'valor_hora':
            $horas = buscarHorasConsumidasMovidesk($contrato['cliente_id'], $competencia); // já existe — mesmo motor da área do cliente
            return $horas * (float) $contrato['valor_hora'];

        case 'banco_horas_minimo':
            $horas = buscarHorasConsumidasMovidesk($contrato['cliente_id'], $competencia);
            $valorConsumido = $horas * (float) $contrato['valor_hora'];
            return max($valorConsumido, (float) $contrato['valor']); // valor = mínimo garantido
    }
    return 0.0;
}

function montarDataVencimento(string $competencia, int $diaVencimento): string
{
    [$ano, $mes] = explode('-', $competencia);
    $ultimoDia = (int) date('t', strtotime("$ano-$mes-01"));
    $dia = min($diaVencimento, $ultimoDia); // protege contra dia 31 em mês de 30
    return sprintf('%s-%s-%02d', $ano, $mes, $dia);
}


// =========================================================
// APROVAÇÃO DE CONTRATO -> TELA DE CONFIRMAÇÃO -> CADASTRO ASAAS
// =========================================================

/**
 * Passo 1 — chamado quando o contrato é marcado como aprovado.
 * NÃO cria nada no ASAAS ainda — só monta o que será mostrado na tela
 * de confirmação (newsiga-confirmacao-asaas.html), incluindo o aviso
 * de "CNPJ já existe" quando for o caso.
 */
function prepararConfirmacaoAsaas(PDO $db, AsaasClient $asaas, int $contratoId): array
{
    $stmt = $db->prepare("
        SELECT c.id AS contrato_id, c.tipo, c.descricao, cl.id AS cliente_id,
               cl.nome, cl.cnpj, cl.email, cl.asaas_customer_id
        FROM contratos c JOIN clientes cl ON cl.id = c.cliente_id
        WHERE c.id = ? AND c.status = 'aprovado'
    ");
    $stmt->execute([$contratoId]);
    $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$contrato) {
        throw new RuntimeException('Contrato não encontrado ou não está aprovado.');
    }

    $jaExiste = null;
    if (!$contrato['asaas_customer_id']) {
        // só vale a pena checar na API se ainda não sabemos o customer_id local
        $jaExiste = $asaas->buscarClientePorDocumento($contrato['cnpj']);
    }

    // Se já existe cadastro no ASAAS, comparamos campo a campo com o que
    // temos localmente — o sistema NUNCA decide sozinho qual valor está
    // certo, só sinaliza a diferença pra você escolher.
    $divergencias = [];
    if ($jaExiste) {
        $divergencias = compararDadosCliente($jaExiste, [
            'name' => $contrato['nome'],
            'email' => $contrato['email'],
        ]);
    }

    return [
        'contrato' => $contrato,
        'parcelas' => buscarParcelasDoContrato($db, $contratoId), // já existente, cadastradas a partir da proposta
        'cliente_ja_existe_no_asaas' => $jaExiste !== null || $contrato['asaas_customer_id'] !== null,
        'asaas_customer_id_existente' => $contrato['asaas_customer_id'] ?? ($jaExiste['id'] ?? null),
        'divergencias' => $divergencias, // ex: [['campo' => 'email', 'no_asaas' => '...', 'local' => '...']]
        // Assinatura já existente no ASAAS pra esse cliente — se houver, o
        // contrato NÃO pode entrar em 'sistema' sem antes você decidir o
        // que fazer com ela (suspender ou deixar como está).
        'assinaturas_existentes' => ($contrato['asaas_customer_id'] || $jaExiste)
            ? $asaas->listarAssinaturasDoCliente($contrato['asaas_customer_id'] ?? $jaExiste['id'])
            : [],
    ];
}

/** Compara campo a campo o cadastro existente no ASAAS com o dado local mais recente */
function compararDadosCliente(array $noAsaas, array $local): array
{
    $divergencias = [];
    $mapaCampos = ['name' => 'Razão social', 'email' => 'E-mail'];

    foreach ($mapaCampos as $campo => $rotulo) {
        $valorAsaas = trim((string) ($noAsaas[$campo] ?? ''));
        $valorLocal = trim((string) ($local[$campo] ?? ''));
        if ($valorAsaas !== '' && $valorLocal !== '' && strcasecmp($valorAsaas, $valorLocal) !== 0) {
            $divergencias[] = [
                'campo' => $campo,
                'rotulo' => $rotulo,
                'no_asaas' => $valorAsaas,
                'local' => $valorLocal,
            ];
        }
    }
    return $divergencias;
}

/**
 * Passo 2 — chamado quando o usuário clica "Confirmar e criar no ASAAS"
 * na tela de confirmação. Só a partir daqui algo é de fato criado.
 *
 * $decisoesDivergencia: quando prepararConfirmacaoAsaas() apontou diferenças
 * (ex: e-mail cadastrado no ASAAS != e-mail local), o usuário precisa dizer
 * explicitamente qual valor prevalece. Formato esperado:
 *   ['email' => 'manter_asaas' | 'usar_local', ...]
 * Nenhuma divergência é resolvida automaticamente — sem decisão, o sistema
 * mantém o que já está no ASAAS e segue com o vínculo mesmo assim.
 */
function confirmarECriarNoAsaas(PDO $db, AsaasClient $asaas, int $contratoId, array $dadosConfirmados, array $decisoesDivergencia = []): void
{
    $existente = $asaas->buscarClientePorDocumento($dadosConfirmados['cnpj']);

    if ($existente) {
        $divergencias = compararDadosCliente($existente, [
            'name' => $dadosConfirmados['nome'],
            'email' => $dadosConfirmados['email'],
        ]);

        $camposParaAtualizar = [];
        foreach ($divergencias as $div) {
            $decisao = $decisoesDivergencia[$div['campo']] ?? null;
            if ($decisao === null) {
                // Não assume nada: se a tela não trouxe uma decisão explícita
                // pra essa divergência, interrompe e devolve pro usuário decidir.
                throw new RuntimeException("Divergência não resolvida no campo '{$div['rotulo']}'. Confirme qual valor manter antes de prosseguir.");
            }
            if ($decisao === 'usar_local') {
                $camposParaAtualizar[$div['campo']] = $div['local'];
            }
            // decisao === 'manter_asaas' -> não faz nada, mantém como está lá
        }

        if ($camposParaAtualizar) {
            $asaas->atualizarCliente($existente['id'], $camposParaAtualizar);
        }
        $cliente = $existente;
    } else {
        $cliente = $asaas->criarOuBuscarCliente(
            $dadosConfirmados['nome'],
            $dadosConfirmados['cnpj'],
            $dadosConfirmados['email']
        );
    }

    // Persiste o vínculo no CLIENTE, não no contrato — assim, o próximo
    // contrato desse mesmo cliente já nasce sabendo o asaas_customer_id
    // e pula a criação (só confirma que os dados batem).
    $db->prepare("UPDATE clientes SET asaas_customer_id = ?, cnpj = ?, email = ? WHERE id = (
        SELECT cliente_id FROM contratos WHERE id = ?
    )")->execute([$cliente['id'], $dadosConfirmados['cnpj'], $dadosConfirmados['email'], $contratoId]);

    $db->prepare("UPDATE contratos SET status = 'ativo' WHERE id = ?")->execute([$contratoId]);

    // Se o contrato for do tipo projeto_parcelado, já cria as cobranças das
    // parcelas nesse momento (elas têm data certa, não dependem do fechamento mensal).
    foreach (buscarParcelasDoContrato($db, $contratoId) as $parcela) {
        // Idempotência: se essa parcela já tem uma cobrança gerada (por
        // exemplo, se essa função for chamada de novo por engano, ou o
        // usuário atualizar a página de confirmação e reenviar), não cria
        // outra. Só parcelas ainda 'a_gerar' e sem asaas_payment_id passam.
        if ($parcela['status'] !== 'a_gerar' || $parcela['asaas_payment_id']) {
            continue;
        }

        $cobranca = $asaas->criarCobranca(
            $cliente['id'],
            $parcela['valor'],
            $parcela['vencimento'],
            "parcela:{$parcela['id']}",
            "Newsiga — parcela {$parcela['numero']} — contrato #$contratoId"
        );
        $db->prepare("UPDATE parcelas SET asaas_payment_id = ?, status = 'gerado' WHERE id = ?")
           ->execute([$cobranca['id'], $parcela['id']]);
    }
}

function buscarParcelasDoContrato(PDO $db, int $contratoId): array
{
    $stmt = $db->prepare("SELECT * FROM parcelas WHERE contrato_id = ? ORDER BY numero");
    $stmt->execute([$contratoId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// =========================================================
// EDIÇÃO MANUAL DE VENCIMENTO (chamado quando você edita o contrato/parcela)
// =========================================================

function alterarVencimentoFatura(PDO $db, AsaasClient $asaas, int $faturaId, string $novoVencimento): void
{
    $fatura = $db->prepare("SELECT asaas_payment_id FROM faturas WHERE id = ?");
    $fatura->execute([$faturaId]);
    $row = $fatura->fetch(PDO::FETCH_ASSOC);

    if (!$row || !$row['asaas_payment_id']) {
        throw new RuntimeException('Fatura ainda não tem cobrança gerada no ASAAS.');
    }

    // Se a cobrança já foi paga, o ASAAS rejeita a alteração — bom avisar
    // o usuário antes, checando o status atual.
    $asaas->atualizarCobranca($row['asaas_payment_id'], ['dueDate' => $novoVencimento]);

    $db->prepare("UPDATE faturas SET vencimento = ? WHERE id = ?")
       ->execute([$novoVencimento, $faturaId]);
}


// =========================================================
// WEBHOOK RECEIVER — mantém status sincronizado sem polling
// =========================================================

header('Content-Type: application/json');

$payload = json_decode(file_get_contents('php://input'), true);

// Validação simples do token configurado no webhook do ASAAS
$tokenRecebido = $_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] ?? '';
if (!hash_equals(ASAAS_WEBHOOK_TOKEN, $tokenRecebido)) {
    http_response_code(401);
    exit;
}

$evento = $payload['event'] ?? null;
$cobranca = $payload['payment'] ?? null;

if ($evento && $cobranca) {
    // externalReference = "fatura:123" — extrai o id da nossa fatura
    $ref = $cobranca['externalReference'] ?? '';
    if (str_starts_with($ref, 'fatura:')) {
        $faturaId = (int) substr($ref, 7);

        $statusMap = [
            'PAYMENT_CONFIRMED' => 'confirmado',
            'PAYMENT_RECEIVED' => 'pago',
            'PAYMENT_OVERDUE' => 'atrasado',
            'PAYMENT_UPDATED' => null, // só sincroniza valor/vencimento, tratado à parte
        ];

        // TODO: abrir conexão PDO aqui e atualizar a tabela `faturas`
        // conforme o evento recebido — inclusive `vencimento` e `valor`
        // quando o evento for PAYMENT_UPDATED (ex: alguém editou direto no ASAAS).
    }
}

http_response_code(200);
echo json_encode(['recebido' => true]);
