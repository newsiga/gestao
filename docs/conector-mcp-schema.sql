-- Tabelas do conector MCP (docs/conector-mcp.md). Rodar no banco PRÓPRIO do
-- conector (ex.: hgnew010_mcp), não no banco do CRM. Só CREATE TABLE: não
-- toca em nenhuma tabela existente.

CREATE TABLE mcp_clientes (
    client_id VARCHAR(64) NOT NULL PRIMARY KEY,
    client_secret_hash CHAR(64) NULL,
    metodo_auth VARCHAR(30) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    redirect_uris TEXT NOT NULL,
    criado_em DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mcp_codigos (
    codigo_hash CHAR(64) NOT NULL PRIMARY KEY,
    client_id VARCHAR(64) NOT NULL,
    redirect_uri VARCHAR(500) NOT NULL,
    code_challenge VARCHAR(128) NOT NULL,
    recurso VARCHAR(200) NOT NULL,
    criado_em DATETIME NOT NULL,
    expira_em DATETIME NOT NULL,
    usado_em DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mcp_tokens (
    token_hash CHAR(64) NOT NULL PRIMARY KEY,
    tipo ENUM('access','refresh') NOT NULL,
    client_id VARCHAR(64) NOT NULL,
    familia CHAR(32) NOT NULL,
    recurso VARCHAR(200) NOT NULL,
    criado_em DATETIME NOT NULL,
    expira_em DATETIME NOT NULL,
    revogado_em DATETIME NULL,
    KEY idx_mcp_tokens_familia (familia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mcp_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    criado_em DATETIME NOT NULL,
    evento VARCHAR(40) NOT NULL,
    tool VARCHAR(60) NULL,
    parametros TEXT NULL,
    client_id VARCHAR(64) NULL,
    ip VARCHAR(45) NULL,
    resultado VARCHAR(20) NOT NULL,
    detalhe VARCHAR(255) NULL,
    duracao_ms INT NULL,
    KEY idx_mcp_log_evento_ip (evento, ip, criado_em),
    KEY idx_mcp_log_evento_cliente (evento, client_id, criado_em),
    KEY idx_mcp_log_data (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
