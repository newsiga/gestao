<?php
/**
 * Ponto de entrada — acessando só "crm.newsiga.com.br" (sem especificar
 * arquivo nenhum), cai aqui. Manda pro painel se já estiver logado, ou
 * pro login se não estiver.
 */
require_once __DIR__ . '/auth.php';

if (esta_logado()) {
    header('Location: /crm-newsiga-painel.php');
} else {
    header('Location: /login.php');
}
exit;
