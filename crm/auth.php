<?php
/**
 * Autenticação simples, usuário único — pro CRM inteiro. Sessão de
 * navegador (cookie), sem "lembrar-me" nem múltiplos perfis, porque só
 * o Felipe usa o sistema.
 *
 * Credenciais ficam em config-crm.php (fora da pasta pública, mesmo
 * lugar do DB_* e ASAAS_*):
 *   define('CRM_LOGIN_USUARIO', 'felipe');
 *   define('CRM_LOGIN_SENHA_HASH', '$2y$...'); // gerado com password_hash()
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php'; // já carrega o config-crm.php

/** Chama no topo de toda tela .html/.php que precisa de login. Redireciona pro login se não estiver autenticado. */
function require_login(): void
{
    // Corrige acentuação — PHP às vezes não manda o charset certo pro
    // navegador mesmo com <meta charset="UTF-8"> no HTML.
    header('Content-Type: text/html; charset=UTF-8');

    if (empty($_SESSION['crm_logado'])) {
        $destino = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: /login.php?voltar=' . urlencode($destino));
        exit;
    }
}

/** Chama no topo de todo endpoint .php que devolve JSON (listar-*, salvar-*, etc). Responde 401 em vez de redirecionar. */
function require_login_api(): void
{
    if (empty($_SESSION['crm_logado'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['erro' => 'Não autenticado. Faça login novamente.']);
        exit;
    }
}

function esta_logado(): bool
{
    return !empty($_SESSION['crm_logado']);
}
