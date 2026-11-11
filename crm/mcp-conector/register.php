<?php
/**
 * Registro dinâmico de cliente OAuth (RFC 7591), em /oauth/register. O
 * Claude registra a si mesmo aqui na primeira conexão. Registrar não dá
 * acesso a nada: o acesso só sai depois do login do Felipe em /oauth/authorize,
 * e só para as redirect_uri permitidas (oauth_redirect_uri_permitida()).
 */

define('MCP_CONECTOR', true);
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/oauth.php';

const REGISTRO_LIMITE_IP_HORA = 10;
const REGISTRO_LIMITE_TOTAL = 200;

mcp_cors();
mcp_responder_options();

if (!mcp_disponivel()) {
    oauth_erro(503, 'temporarily_unavailable', 'Conector desativado.');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    oauth_erro(405, 'invalid_request', 'Use POST.');
}
if (mcp_contar_eventos('registro', 'ip', mcp_ip(), 3600) >= REGISTRO_LIMITE_IP_HORA) {
    oauth_erro(429, 'invalid_request', 'Muitos registros a partir deste endereço. Tente mais tarde.');
}
if ((int) mcp_db()->query('SELECT COUNT(*) FROM mcp_clientes')->fetchColumn() >= REGISTRO_LIMITE_TOTAL) {
    oauth_erro(400, 'invalid_request', 'Limite de clientes registrados atingido.');
}

$pedido = json_decode(file_get_contents('php://input', false, null, 0, 65536), true);
if (!is_array($pedido)) {
    oauth_erro(400, 'invalid_client_metadata', 'Corpo deve ser JSON.');
}

$redirects = $pedido['redirect_uris'] ?? null;
if (!is_array($redirects) || !$redirects || count($redirects) > 5) {
    oauth_erro(400, 'invalid_redirect_uri', 'Informe de 1 a 5 redirect_uris.');
}
foreach ($redirects as $uri) {
    if (!is_string($uri) || strlen($uri) > 500 || !oauth_redirect_uri_permitida($uri)) {
        mcp_log('registro', 'negado', ['detalhe' => 'redirect_uri não permitida: ' . (is_string($uri) ? substr($uri, 0, 150) : '?')]);
        oauth_erro(400, 'invalid_redirect_uri', 'redirect_uri não permitida para este servidor.');
    }
}

$metodo = $pedido['token_endpoint_auth_method'] ?? 'none';
if (!in_array($metodo, OAUTH_METODOS_AUTH, true)) {
    oauth_erro(400, 'invalid_client_metadata', 'token_endpoint_auth_method não suportado.');
}
$grants = $pedido['grant_types'] ?? ['authorization_code', 'refresh_token'];
if (!is_array($grants) || array_diff($grants, ['authorization_code', 'refresh_token'])) {
    oauth_erro(400, 'invalid_client_metadata', 'grant_types suportados: authorization_code e refresh_token.');
}
$nome = is_string($pedido['client_name'] ?? null) ? mb_substr(trim($pedido['client_name']), 0, 100) : 'Cliente sem nome';

$clientId = 'nsg_' . mcp_aleatorio(18);
$segredo = $metodo === 'none' ? null : mcp_aleatorio(32);
$agora = time();

mcp_db()->prepare('
    INSERT INTO mcp_clientes (client_id, client_secret_hash, metodo_auth, nome, redirect_uris, criado_em)
    VALUES (?, ?, ?, ?, ?, ?)
')->execute([$clientId, $segredo === null ? null : mcp_hash($segredo), $metodo, $nome, json_encode(array_values($redirects), JSON_UNESCAPED_SLASHES), mcp_agora()]);

mcp_log('registro', 'ok', ['client_id' => $clientId, 'detalhe' => $nome]);

$resposta = [
    'client_id'                  => $clientId,
    'client_id_issued_at'        => $agora,
    'client_name'                => $nome,
    'redirect_uris'              => array_values($redirects),
    'token_endpoint_auth_method' => $metodo,
    'grant_types'                => array_values($grants),
    'response_types'             => ['code'],
];
if ($segredo !== null) {
    $resposta['client_secret'] = $segredo;
    $resposta['client_secret_expires_at'] = 0;
}
mcp_json(201, $resposta);
