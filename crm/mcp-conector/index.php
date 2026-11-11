<?php
/**
 * Endpoint MCP (Streamable HTTP), publicado em https://crm.newsiga.com.br/mcp
 * pela regra do crm/.htaccess. Somente leitura. Ver docs/conector-mcp.md.
 *
 * Funciona sem sessão: cada POST traz uma mensagem JSON-RPC e recebe a
 * resposta em JSON (o protocolo permite responder sem SSE). Por isso GET
 * (canal de notificações do servidor) e DELETE (encerrar sessão) devolvem
 * 405, como a especificação prevê para servidores sem esses recursos.
 */

define('MCP_CONECTOR', true);
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/oauth.php';
require_once __DIR__ . '/inc/tools.php';

const MCP_VERSOES_PROTOCOLO = ['2025-11-25', '2025-06-18', '2025-03-26', '2024-11-05'];
const MCP_LIMITE_POR_MINUTO = 30;
const MCP_LIMITE_POR_DIA = 500;
const MCP_LIMITE_NEGADOS_IP_10MIN = 60;

mcp_cors();
mcp_responder_options();

if (!mcp_disponivel()) {
    mcp_json(503, ['erro' => 'Conector desativado.']);
}

$metodoHttp = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$cabecalhoAuth = 'WWW-Authenticate: Bearer resource_metadata="' . mcp_base_url() . '/.well-known/oauth-protected-resource"';

// ---- Autenticação (antes de tudo, inclusive do GET)
$tokenInfo = null;
$token = oauth_token_da_requisicao();
if ($token !== null) {
    $tokenInfo = oauth_validar_access_token($token);
}
if ($tokenInfo === null) {
    if (mcp_contar_eventos('negado', 'ip', mcp_ip(), 600) >= MCP_LIMITE_NEGADOS_IP_10MIN) {
        mcp_json(429, ['erro' => 'Muitas tentativas sem autorização. Tente mais tarde.'], ['Retry-After: 600']);
    }
    mcp_log('negado', 'negado', ['detalhe' => $token === null ? 'sem token' : 'token inválido ou expirado']);
    mcp_json(401, ['error' => 'invalid_token', 'error_description' => 'Autorização ausente, inválida ou expirada.'], [
        $token === null ? $cabecalhoAuth : $cabecalhoAuth . ', error="invalid_token"',
    ]);
}
$clientId = $tokenInfo['client_id'];

if ($metodoHttp !== 'POST') {
    mcp_json(405, ['erro' => 'Use POST.'], ['Allow: POST, OPTIONS']);
}

// ---- Leitura da mensagem
$corpo = file_get_contents('php://input', false, null, 0, 1048576);
$mensagem = json_decode($corpo, true);
if (!is_array($mensagem)) {
    mcp_json(400, rpc_erro(null, -32700, 'JSON inválido.'));
}

$lote = array_is_list($mensagem) && $mensagem !== [];
$mensagens = $lote ? $mensagem : [$mensagem];
$respostas = [];
foreach ($mensagens as $m) {
    $resposta = is_array($m) ? rpc_processar($m, $clientId) : rpc_erro(null, -32600, 'Requisição inválida.');
    if ($resposta !== null) {
        $respostas[] = $resposta;
    }
}

if (!$respostas) {
    // Só notificações (ex.: notifications/initialized) ou respostas: 202 sem corpo.
    http_response_code(202);
    exit;
}
mcp_json(200, $lote ? $respostas : $respostas[0]);

// ---------------------------------------------------------------- JSON-RPC

function rpc_erro($id, int $codigo, string $mensagem): array
{
    return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $codigo, 'message' => $mensagem]];
}

function rpc_resultado($id, $resultado): array
{
    return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $resultado];
}

/** Processa uma mensagem; devolve null para notificações e respostas (não têm resposta). */
function rpc_processar(array $m, string $clientId): ?array
{
    $temId = array_key_exists('id', $m);
    $id = $m['id'] ?? null;
    $metodo = $m['method'] ?? null;

    if (!is_string($metodo)) {
        return $temId && !isset($m['result']) && !isset($m['error']) ? rpc_erro($id, -32600, 'Requisição inválida.') : null;
    }
    if (!$temId) {
        return null; // notificação
    }
    $params = is_array($m['params'] ?? null) ? $m['params'] : [];

    switch ($metodo) {
        case 'initialize':
            $pedida = $params['protocolVersion'] ?? '';
            return rpc_resultado($id, [
                'protocolVersion' => in_array($pedida, MCP_VERSOES_PROTOCOLO, true) ? $pedida : MCP_VERSOES_PROTOCOLO[1],
                'capabilities'    => ['tools' => ['listChanged' => false]],
                'serverInfo'      => ['name' => MCP_NOME, 'title' => 'CRM Newsiga — fluxo de caixa', 'version' => MCP_VERSAO],
                'instructions'    => 'Consulta, somente leitura, do fluxo de caixa da Newsiga no CRM: receitas (contratos, faturas e '
                    . 'cobranças do ASAAS) e despesas com fornecedores. Valores em reais (R$), datas em AAAA-MM-DD, fuso '
                    . 'America/Recife. O mês de cada lançamento é o do vencimento. O CRM não tem saldo bancário. Ao responder, '
                    . 'formate valores como R$ 1.234,56 e cite as observações que vierem no resultado.',
            ]);

        case 'ping':
            return rpc_resultado($id, new stdClass());

        case 'tools/list':
            return rpc_resultado($id, ['tools' => mcp_tools_definicoes()]);

        case 'tools/call':
            return rpc_chamar_tool($id, $params, $clientId);
    }
    return rpc_erro($id, -32601, "Método não suportado: $metodo");
}

function rpc_chamar_tool($id, array $params, string $clientId): array
{
    $nome = is_string($params['name'] ?? null) ? $params['name'] : '';
    $argumentos = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

    if (mcp_contar_eventos('tools/call', 'client_id', $clientId, 60) >= MCP_LIMITE_POR_MINUTO
        || mcp_contar_eventos('tools/call', 'client_id', $clientId, 86400) >= MCP_LIMITE_POR_DIA) {
        mcp_log('limite', 'negado', ['tool' => $nome, 'client_id' => $clientId, 'parametros' => $argumentos]);
        return rpc_erro($id, -32000, 'Limite de consultas atingido (' . MCP_LIMITE_POR_MINUTO . ' por minuto, ' . MCP_LIMITE_POR_DIA . ' por dia). Tente de novo em instantes.');
    }

    $inicio = microtime(true);
    try {
        $dados = mcp_tools_executar($nome, $argumentos);
        mcp_log('tools/call', 'ok', [
            'tool' => $nome, 'client_id' => $clientId, 'parametros' => $argumentos,
            'duracao_ms' => (int) ((microtime(true) - $inicio) * 1000),
        ]);
        return rpc_resultado($id, [
            'content'           => [['type' => 'text', 'text' => json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)]],
            'structuredContent' => $dados,
            'isError'           => false,
        ]);
    } catch (DomainException $e) {
        mcp_log('tools/call', 'erro', ['tool' => $nome, 'client_id' => $clientId, 'parametros' => $argumentos, 'detalhe' => $e->getMessage()]);
        return rpc_erro($id, -32602, $e->getMessage());
    } catch (InvalidArgumentException $e) {
        // Erro de parâmetro volta como resultado de tool com isError, para o Claude corrigir e tentar de novo.
        mcp_log('tools/call', 'erro', ['tool' => $nome, 'client_id' => $clientId, 'parametros' => $argumentos, 'detalhe' => $e->getMessage()]);
        return rpc_resultado($id, ['content' => [['type' => 'text', 'text' => $e->getMessage()]], 'isError' => true]);
    } catch (Throwable $e) {
        error_log('MCP tools/call ' . $nome . ': ' . $e->getMessage());
        mcp_log('tools/call', 'erro', ['tool' => $nome, 'client_id' => $clientId, 'parametros' => $argumentos, 'detalhe' => substr($e->getMessage(), 0, 255)]);
        return rpc_resultado($id, ['content' => [['type' => 'text', 'text' => 'Erro interno ao consultar o CRM. Detalhe registrado no log do servidor.']], 'isError' => true]);
    }
}
