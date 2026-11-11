<?php
/**
 * OAuth 2.1 mínimo para o conector MCP: registro dinâmico de cliente
 * (RFC 7591), código de autorização com PKCE S256 e refresh token com
 * rotação. Só um usuário (o login do CRM) pode autorizar.
 *
 * Tokens, códigos e segredos nunca são gravados em claro: o banco guarda
 * só o SHA-256. Quem ler a tabela não consegue usar o que está nela.
 */

if (!defined('MCP_CONECTOR')) {
    http_response_code(404);
    exit;
}

const OAUTH_TTL_CODIGO = 300;           // 5 minutos
const OAUTH_TTL_ACCESS = 3600;          // 1 hora
const OAUTH_TTL_REFRESH = 30 * 86400;   // 30 dias, renovados a cada uso (rotação)
const OAUTH_METODOS_AUTH = ['none', 'client_secret_post', 'client_secret_basic'];

/**
 * redirect_uri aceitas no registro: as do Claude (web, desktop e celular
 * usam o mesmo callback), qualquer porta do localhost (MCP Inspector) e as
 * extras do config. Sem isso, qualquer um poderia registrar um cliente que
 * manda o código de autorização para um site próprio.
 */
function oauth_redirect_uri_permitida(string $uri): bool
{
    $partes = parse_url($uri);
    if (!$partes || empty($partes['scheme']) || empty($partes['host']) || isset($partes['fragment'])) {
        return false;
    }
    $doClaude = ['https://claude.ai/api/mcp/auth_callback', 'https://claude.com/api/mcp/auth_callback'];
    if (in_array($uri, $doClaude, true)) {
        return true;
    }
    if ($partes['scheme'] === 'http' && in_array($partes['host'], ['localhost', '127.0.0.1'], true)) {
        return true;
    }
    return defined('MCP_REDIRECT_URIS_EXTRAS') && is_array(MCP_REDIRECT_URIS_EXTRAS)
        && in_array($uri, MCP_REDIRECT_URIS_EXTRAS, true);
}

/** O parâmetro "resource" (RFC 8707) é opcional; se vier, tem de ser este servidor. */
function oauth_recurso_valido(?string $recurso): bool
{
    if ($recurso === null || $recurso === '') {
        return true;
    }
    return rtrim($recurso, '/') === mcp_recurso();
}

function oauth_buscar_cliente(string $clientId): ?array
{
    if ($clientId === '' || strlen($clientId) > 64) {
        return null;
    }
    $stmt = mcp_db()->prepare('SELECT * FROM mcp_clientes WHERE client_id = ?');
    $stmt->execute([$clientId]);
    $cliente = $stmt->fetch();
    if (!$cliente) {
        return null;
    }
    $cliente['redirect_uris'] = json_decode($cliente['redirect_uris'], true) ?: [];
    return $cliente;
}

function oauth_pkce_valido(string $verificador, string $desafio): bool
{
    if (!preg_match('/^[A-Za-z0-9\-._~]{43,128}$/', $verificador)) {
        return false;
    }
    $calculado = rtrim(strtr(base64_encode(hash('sha256', $verificador, true)), '+/', '-_'), '=');
    return hash_equals($desafio, $calculado);
}

/** Cria o código de autorização e devolve o valor em claro (só esta vez). */
function oauth_criar_codigo(string $clientId, string $redirectUri, string $desafio): string
{
    $codigo = mcp_aleatorio(32);
    mcp_db()->prepare('
        INSERT INTO mcp_codigos (codigo_hash, client_id, redirect_uri, code_challenge, recurso, criado_em, expira_em)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ')->execute([
        mcp_hash($codigo), $clientId, $redirectUri, $desafio, mcp_recurso(),
        mcp_agora(), date('Y-m-d H:i:s', time() + OAUTH_TTL_CODIGO),
    ]);
    return $codigo;
}

/**
 * Emite access + refresh token. A "família" liga todos os tokens que
 * nascem de uma mesma autorização: se um refresh token já usado aparecer
 * de novo (sinal de vazamento), a família inteira é revogada.
 */
function oauth_emitir_tokens(string $clientId, ?string $familia = null): array
{
    $familia = $familia ?? bin2hex(random_bytes(16));
    $access = 'nsg_at_' . mcp_aleatorio(32);
    $refresh = 'nsg_rt_' . mcp_aleatorio(32);
    $agora = mcp_agora();

    $insert = mcp_db()->prepare('
        INSERT INTO mcp_tokens (token_hash, tipo, client_id, familia, recurso, criado_em, expira_em)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $insert->execute([mcp_hash($access), 'access', $clientId, $familia, mcp_recurso(), $agora, date('Y-m-d H:i:s', time() + OAUTH_TTL_ACCESS)]);
    $insert->execute([mcp_hash($refresh), 'refresh', $clientId, $familia, mcp_recurso(), $agora, date('Y-m-d H:i:s', time() + OAUTH_TTL_REFRESH)]);

    return [
        'access_token'  => $access,
        'token_type'    => 'Bearer',
        'expires_in'    => OAUTH_TTL_ACCESS,
        'refresh_token' => $refresh,
        'scope'         => 'leitura',
    ];
}

function oauth_revogar_familia(string $familia): void
{
    mcp_db()->prepare('UPDATE mcp_tokens SET revogado_em = ? WHERE familia = ? AND revogado_em IS NULL')
        ->execute([mcp_agora(), $familia]);
}

/** Valida o access token do cabeçalho Authorization. Devolve a linha do token ou null. */
function oauth_validar_access_token(string $token): ?array
{
    if (!str_starts_with($token, 'nsg_at_') || strlen($token) > 100) {
        return null;
    }
    $stmt = mcp_db()->prepare("SELECT * FROM mcp_tokens WHERE token_hash = ? AND tipo = 'access'");
    $stmt->execute([mcp_hash($token)]);
    $linha = $stmt->fetch();
    if (!$linha || $linha['revogado_em'] !== null || $linha['expira_em'] <= mcp_agora()) {
        return null;
    }
    if (rtrim($linha['recurso'], '/') !== mcp_recurso()) {
        return null;
    }
    return $linha;
}

/** Lê o token Bearer da requisição (o LiteSpeed da Hostgator repassa o cabeçalho; testado em 2026-11-10). */
function oauth_token_da_requisicao(): ?string
{
    $cabecalho = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($cabecalho === '' && function_exists('apache_request_headers')) {
        $todos = array_change_key_case(apache_request_headers());
        $cabecalho = $todos['authorization'] ?? '';
    }
    if (!preg_match('/^Bearer\s+(\S+)$/i', trim($cabecalho), $m)) {
        return null;
    }
    return $m[1];
}

/** Monta a URL de retorno ao cliente, preservando a query que a redirect_uri já tiver. */
function oauth_url_retorno(string $redirectUri, array $parametros): string
{
    $parametros = array_filter($parametros, fn($v) => $v !== null && $v !== '');
    return $redirectUri . (str_contains($redirectUri, '?') ? '&' : '?') . http_build_query($parametros);
}

function oauth_erro(int $status, string $erro, string $descricao, array $cabecalhos = []): never
{
    mcp_json($status, ['error' => $erro, 'error_description' => $descricao], $cabecalhos);
}
