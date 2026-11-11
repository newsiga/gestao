<?php
/**
 * Base comum do conector MCP (servidor de leitura do fluxo de caixa para o
 * Claude). Visão geral, instalação e operação em docs/conector-mcp.md.
 *
 * Os arquivos de inc/ só funcionam incluídos pelos pontos de entrada da
 * pasta de cima (index.php, authorize.php, token.php, register.php,
 * well-known.php). Acessados direto pela URL, respondem 404.
 *
 * Constantes lidas do config-crm.php (fora do Git, ver docs/deploy.md):
 *   MCP_DB_NAME, MCP_DB_USER, MCP_DB_PASS  banco próprio do conector (tokens,
 *       log, limite de requisições). O usuário tem SELECT no banco do CRM
 *       (DB_NAME) e nada mais lá: o conector nunca grava no CRM.
 *   MCP_ATIVO          opcional; false desliga o conector inteiro na hora.
 *   MCP_BASE_URL       opcional; padrão https://crm.newsiga.com.br.
 *   MCP_REDIRECT_URIS_EXTRAS  opcional; array de redirect_uri aceitas além
 *       das do Claude e do localhost (MCP Inspector).
 *   ASAAS_API_KEY, ASAAS_BASE_URL  as mesmas do CRM; o conector só faz GET.
 */

if (!defined('MCP_CONECTOR')) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../../db.php'; // carrega o config-crm.php

date_default_timezone_set('America/Recife');

const MCP_VERSAO = '1.0.0';
const MCP_NOME = 'crm-newsiga-fluxo-caixa';

function mcp_base_url(): string
{
    return defined('MCP_BASE_URL') ? rtrim(MCP_BASE_URL, '/') : 'https://crm.newsiga.com.br';
}

/** URL canônica do servidor MCP — é o "resource" do OAuth e a audiência dos tokens. */
function mcp_recurso(): string
{
    return mcp_base_url() . '/mcp';
}

function mcp_disponivel(): bool
{
    if (defined('MCP_ATIVO') && !MCP_ATIVO) {
        return false;
    }
    return defined('MCP_DB_NAME') && defined('MCP_DB_USER') && defined('MCP_DB_PASS');
}

/**
 * Conexão com o usuário restrito do conector. O banco padrão é o do próprio
 * conector; as tabelas do CRM são lidas pelo nome qualificado (crm_t()).
 */
function mcp_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . MCP_DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, MCP_DB_USER, MCP_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/** Nome qualificado de uma tabela do CRM, ex.: crm_t('faturas') -> `banco_crm`.`faturas`. */
function crm_t(string $tabela): string
{
    return '`' . str_replace('`', '', DB_NAME) . '`.`' . str_replace('`', '', $tabela) . '`';
}

function mcp_agora(): string
{
    return date('Y-m-d H:i:s');
}

function mcp_hash(string $valor): string
{
    return hash('sha256', $valor);
}

/** Valor aleatório em base64url, para códigos, tokens e client_id. */
function mcp_aleatorio(int $bytes = 32): string
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

function mcp_ip(): string
{
    // Sem proxy reverso na frente da Hostgator: o REMOTE_ADDR é o IP real.
    // X-Forwarded-For não é usado porque qualquer um pode forjá-lo.
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/**
 * CORS para o MCP Inspector no navegador e para o claude.ai. Não é
 * proteção (o que protege é o token), só permite a chamada do navegador.
 */
function mcp_cors(): void
{
    $origem = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origem === '') {
        return;
    }
    $permitida = in_array($origem, ['https://claude.ai', 'https://claude.com'], true)
        || preg_match('#^http://(localhost|127\.0\.0\.1)(:\d+)?$#', $origem);
    if (!$permitida) {
        return;
    }
    header('Access-Control-Allow-Origin: ' . $origem);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, Mcp-Protocol-Version, Mcp-Session-Id, Last-Event-ID');
    header('Access-Control-Expose-Headers: WWW-Authenticate, Mcp-Session-Id');
    header('Access-Control-Max-Age: 600');
}

/** Responde à pré-verificação CORS e encerra. */
function mcp_responder_options(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        mcp_cors();
        http_response_code(204);
        exit;
    }
}

function mcp_json(int $status, $dados, array $cabecalhos = []): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    foreach ($cabecalhos as $cabecalho) {
        header($cabecalho);
    }
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Registra um evento em mcp_log. Nunca derruba a requisição: se o log
 * falhar, vai para o error_log do PHP.
 */
function mcp_log(string $evento, string $resultado, array $extra = []): void
{
    try {
        $parametros = $extra['parametros'] ?? null;
        mcp_db()->prepare('
            INSERT INTO mcp_log (criado_em, evento, tool, parametros, client_id, ip, resultado, detalhe, duracao_ms)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ')->execute([
            mcp_agora(),
            substr($evento, 0, 40),
            isset($extra['tool']) ? substr((string) $extra['tool'], 0, 60) : null,
            $parametros === null ? null : substr(json_encode($parametros, JSON_UNESCAPED_UNICODE), 0, 2000),
            isset($extra['client_id']) ? substr((string) $extra['client_id'], 0, 64) : null,
            mcp_ip(),
            substr($resultado, 0, 20),
            isset($extra['detalhe']) ? substr((string) $extra['detalhe'], 0, 255) : null,
            $extra['duracao_ms'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('mcp_log falhou: ' . $e->getMessage());
    }
}

/** Quantos eventos com esse filtro aconteceram nos últimos $segundos (base do limite de requisições). */
function mcp_contar_eventos(string $evento, string $campo, ?string $valor, int $segundos): int
{
    if (!in_array($campo, ['ip', 'client_id', 'todos'], true)) {
        throw new InvalidArgumentException('Campo de contagem inválido');
    }
    $desde = date('Y-m-d H:i:s', time() - $segundos);
    $sql = 'SELECT COUNT(*) FROM mcp_log WHERE evento = ? AND criado_em >= ?';
    $parametros = [$evento, $desde];
    if ($campo !== 'todos') {
        $sql .= " AND $campo = ?";
        $parametros[] = $valor;
    }
    $stmt = mcp_db()->prepare($sql);
    $stmt->execute($parametros);
    return (int) $stmt->fetchColumn();
}
