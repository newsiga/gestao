<?php
/**
 * Documentos de descoberta do OAuth, publicados pelo crm/.htaccess em:
 *   /.well-known/oauth-protected-resource[/mcp]   (RFC 9728) -> ?doc=recurso
 *   /.well-known/oauth-authorization-server[/mcp] (RFC 8414) -> ?doc=servidor
 * É por eles que o Claude descobre onde fazer login e pegar o token.
 */

define('MCP_CONECTOR', true);
require_once __DIR__ . '/inc/bootstrap.php';

mcp_cors();
mcp_responder_options();

$base = mcp_base_url();

if (($_GET['doc'] ?? '') === 'recurso') {
    mcp_json(200, [
        'resource'                 => mcp_recurso(),
        'authorization_servers'    => [$base],
        'bearer_methods_supported' => ['header'],
        'scopes_supported'         => ['leitura'],
        'resource_name'            => 'CRM Newsiga — fluxo de caixa',
    ]);
}

if (($_GET['doc'] ?? '') === 'servidor') {
    mcp_json(200, [
        'issuer'                                => $base,
        'authorization_endpoint'                => "$base/oauth/authorize",
        'token_endpoint'                        => "$base/oauth/token",
        'registration_endpoint'                 => "$base/oauth/register",
        'response_types_supported'              => ['code'],
        'grant_types_supported'                 => ['authorization_code', 'refresh_token'],
        'code_challenge_methods_supported'      => ['S256'],
        'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post', 'client_secret_basic'],
        'scopes_supported'                      => ['leitura'],
    ]);
}

mcp_json(404, ['erro' => 'Documento não encontrado.']);
