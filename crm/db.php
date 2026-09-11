<?php
/**
 * Conexão PDO única e reaproveitada com o banco do CRM.
 *
 * As credenciais NÃO ficam neste arquivo (que é público, dentro da pasta
 * do subdomínio) — ficam em um arquivo separado, FORA da pasta pública
 * (fora de public_html ou da pasta do subdomínio), que ninguém acessa
 * pela internet. Ver instruções de onde criar esse arquivo.
 */

// Ajuste este caminho para onde você criar o config-crm.php — sempre UM
// NÍVEL ACIMA da pasta pública do subdomínio. Ex: se o site fica em
// /home/hgnew010/crm.newsiga.com.br/, o config fica em /home/hgnew010/config-crm.php
$configPath = __DIR__ . '/../config-crm.php';

if (!file_exists($configPath)) {
    throw new RuntimeException('Arquivo de configuração não encontrado: ' . $configPath);
}

require_once $configPath; // define as constantes DB_HOST, DB_NAME, DB_USER, DB_PASS

function getDb(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}
