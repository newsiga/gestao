<?php
/**
 * Endpoint de token do OAuth, em /oauth/token. Troca o código de
 * autorização (com PKCE) por access + refresh token, e renova o par a
 * partir do refresh token (rotação: o refresh usado deixa de valer).
 */

define('MCP_CONECTOR', true);
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/oauth.php';

const TOKEN_LIMITE_FALHAS_IP_15MIN = 20;

mcp_cors();
mcp_responder_options();

if (!mcp_disponivel()) {
    oauth_erro(503, 'temporarily_unavailable', 'Conector desativado.');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    oauth_erro(405, 'invalid_request', 'Use POST.');
}
if (mcp_contar_eventos('token', 'ip', mcp_ip(), 900) >= TOKEN_LIMITE_FALHAS_IP_15MIN) {
    oauth_erro(429, 'invalid_request', 'Muitas tentativas. Tente mais tarde.');
}

// O padrão é form-urlencoded; aceita JSON também, por tolerância.
$p = $_POST;
if (!$p && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'json')) {
    $p = json_decode(file_get_contents('php://input', false, null, 0, 65536), true) ?: [];
}
$p = array_map(fn($v) => is_string($v) ? $v : '', $p);

// ---- Autenticação do cliente
$clientId = $p['client_id'] ?? '';
$segredo = $p['client_secret'] ?? null;
$basic = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Basic\s+(\S+)$/i', $basic, $m)) {
    $par = explode(':', (string) base64_decode($m[1], true), 2);
    if (count($par) === 2) {
        $clientId = urldecode($par[0]);
        $segredo = urldecode($par[1]);
    }
}

$cliente = oauth_buscar_cliente($clientId);
if (!$cliente) {
    falha('invalid_client', 'Cliente desconhecido.', 401);
}
if ($cliente['metodo_auth'] !== 'none') {
    if (!is_string($segredo) || $segredo === '' || !hash_equals($cliente['client_secret_hash'], mcp_hash($segredo))) {
        falha('invalid_client', 'Credenciais do cliente inválidas.', 401, $clientId);
    }
}

if (!oauth_recurso_valido($p['resource'] ?? null)) {
    falha('invalid_target', 'resource deve ser ' . mcp_recurso(), 400, $clientId);
}

$grant = $p['grant_type'] ?? '';

if ($grant === 'authorization_code') {
    $codigo = $p['code'] ?? '';
    $stmt = mcp_db()->prepare('SELECT * FROM mcp_codigos WHERE codigo_hash = ?');
    $stmt->execute([mcp_hash($codigo)]);
    $linha = $stmt->fetch();

    if (!$linha || $linha['client_id'] !== $clientId) {
        falha('invalid_grant', 'Código inválido.', 400, $clientId);
    }
    if ($linha['usado_em'] !== null || $linha['expira_em'] <= mcp_agora()) {
        falha('invalid_grant', 'Código expirado ou já usado.', 400, $clientId);
    }
    if (($p['redirect_uri'] ?? '') !== $linha['redirect_uri']) {
        falha('invalid_grant', 'redirect_uri diferente da usada na autorização.', 400, $clientId);
    }
    if (!oauth_pkce_valido($p['code_verifier'] ?? '', $linha['code_challenge'])) {
        falha('invalid_grant', 'code_verifier inválido.', 400, $clientId);
    }
    // Marca como usado de forma atômica: duas trocas simultâneas do mesmo código, só uma vale.
    $marca = mcp_db()->prepare('UPDATE mcp_codigos SET usado_em = ? WHERE codigo_hash = ? AND usado_em IS NULL');
    $marca->execute([mcp_agora(), $linha['codigo_hash']]);
    if ($marca->rowCount() !== 1) {
        falha('invalid_grant', 'Código já usado.', 400, $clientId);
    }

    mcp_log('token_emitido', 'ok', ['client_id' => $clientId, 'detalhe' => 'authorization_code']);
    mcp_json(200, oauth_emitir_tokens($clientId), ['Pragma: no-cache']);
}

if ($grant === 'refresh_token') {
    $refresh = $p['refresh_token'] ?? '';
    $stmt = mcp_db()->prepare("SELECT * FROM mcp_tokens WHERE token_hash = ? AND tipo = 'refresh'");
    $stmt->execute([mcp_hash($refresh)]);
    $linha = $stmt->fetch();

    if (!$linha || $linha['client_id'] !== $clientId) {
        falha('invalid_grant', 'Refresh token inválido.', 400, $clientId);
    }
    if ($linha['revogado_em'] !== null) {
        // Refresh já usado aparecendo de novo: possível vazamento. Derruba a família inteira.
        oauth_revogar_familia($linha['familia']);
        mcp_log('token', 'negado', ['client_id' => $clientId, 'detalhe' => 'refresh reutilizado; família revogada']);
        oauth_erro(400, 'invalid_grant', 'Refresh token já usado. Autorize o conector de novo.');
    }
    if ($linha['expira_em'] <= mcp_agora()) {
        falha('invalid_grant', 'Refresh token expirado. Autorize o conector de novo.', 400, $clientId);
    }
    $revoga = mcp_db()->prepare('UPDATE mcp_tokens SET revogado_em = ? WHERE token_hash = ? AND revogado_em IS NULL');
    $revoga->execute([mcp_agora(), $linha['token_hash']]);
    if ($revoga->rowCount() !== 1) {
        falha('invalid_grant', 'Refresh token já usado.', 400, $clientId);
    }

    mcp_log('token_emitido', 'ok', ['client_id' => $clientId, 'detalhe' => 'refresh_token']);
    mcp_json(200, oauth_emitir_tokens($clientId, $linha['familia']), ['Pragma: no-cache']);
}

falha('unsupported_grant_type', 'grant_type suportados: authorization_code e refresh_token.', 400, $clientId);

/** Registra a falha (conta para o limite por IP) e responde o erro OAuth. */
function falha(string $erro, string $descricao, int $status, ?string $clientId = null): never
{
    mcp_log('token', 'negado', ['client_id' => $clientId, 'detalhe' => "$erro: $descricao"]);
    $cabecalhos = $status === 401 ? ['WWW-Authenticate: Basic realm="oauth"'] : [];
    oauth_erro($status, $erro, $descricao, $cabecalhos);
}
