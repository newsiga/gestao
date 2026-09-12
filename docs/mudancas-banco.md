# Registro de mudanças no banco de dados

Log informal de toda alteração de schema aplicada em produção — sem ferramenta de migração automatizada neste projeto (ver `README.md`, seção 3), este arquivo é o que evita perder o controle de qual alteração já rodou em qual ambiente.

Cada entrada: data, o que mudou, e o comando exato executado.

---

## 2026-09-12 — Criação do módulo de despesas/fornecedores

Três tabelas novas, sem impacto em tabelas existentes (nenhum `ALTER`, só `CREATE TABLE`). Contexto completo em `docs/despesas-fornecedores-crm.md`.

```sql
CREATE TABLE fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    tipo ENUM('operacional','fixo') NOT NULL,
    movidesk_technician_name VARCHAR(255) NULL,
    forma_pagamento VARCHAR(100) NULL,
    status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE contratos_fornecedor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fornecedor_id INT NOT NULL,
    tipo ENUM('mensalidade_fixa','hora_aberta','banco_horas_minimo','banco_horas_consumo') NOT NULL,
    valor DECIMAL(10,2) NULL,
    valor_hora DECIMAL(10,2) NULL,
    valor_hora_excedente DECIMAL(10,2) NULL,
    horas_banco DECIMAL(6,2) NULL,
    horas_minimas DECIMAL(6,2) NULL,
    dia_vencimento TINYINT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    status ENUM('rascunho','ativo','concluido','encerrado') NOT NULL DEFAULT 'rascunho',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
);

CREATE TABLE despesas_competencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contrato_fornecedor_id INT NOT NULL,
    competencia CHAR(7) NOT NULL,
    horas_consumidas DECIMAL(6,2) NULL,
    valor DECIMAL(10,2) NOT NULL,
    vencimento DATE NOT NULL,
    status ENUM('a_pagar','pago','atrasado') NOT NULL DEFAULT 'a_pagar',
    origem ENUM('movidesk_automatico','manual') NOT NULL DEFAULT 'manual',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_fornecedor_id) REFERENCES contratos_fornecedor(id)
);
```

Divergências deliberadas em relação à modelagem proposta em `docs/despesas-fornecedores-crm.md` (o documento é ponto de partida, não especificação rígida — ver `README.md`, seção 1):

- `criado_em`/`atualizado_em` em vez de `created_at`/`updated_at` — convenção real encontrada em `contratos.criado_em` (via `listar-contratos.php`) e `consumo_movidesk_cache.atualizado_em` (via `movidesk-client.php`).
- `contratos_fornecedor.tipo` usa os 4 tipos recorrentes reais de `contratos` (`mensalidade_fixa`, `hora_aberta`, `banco_horas_minimo`, `banco_horas_consumo`), não os 3 supostos no documento original. `projeto_parcelado` não entra — um projeto pontual de fornecedor é um lançamento manual único em `despesas_competencia`, sem precisar do aparato de parcelas.
- `contratos_fornecedor.status` tem 4 valores (`rascunho`/`ativo`/`concluido`/`encerrado`), sem `'aprovado'` — esse estado intermediário só existe do lado de receita como gatilho da tela de confirmação ASAAS, que não se aplica a fornecedor.
- Campo se chama `movidesk_technician_name` (não `movidesk_technician_id`) — a integração Movidesk real (`movidesk-client.php`) identifica contratos/recursos por **nome**, não por ID numérico (mesmo padrão de `contratos.movidesk_contract_name`).

Comando executado (via SSH, produção):
```bash
ssh -p 2222 hgnew010@br396.hostgator.com.br "cd repos/gestao && php -r \"require 'crm/db.php'; \\\$db = getDb(); \\\$db->exec(file_get_contents('php://stdin'));\" < schema.sql"
```
