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

---

## 2026-09-12 — Remoção do banco de horas em contratos_fornecedor

Diferente do cliente, fornecedor não tem contrato de banco de horas — só
fixo mensal ou hora aberta (uma taxa por relação/cliente atendido, ex:
Robson tem uma taxa pra HubVision-clientes, outra pra HubVision-TOTVS,
outra pra MobCode). Removidos `banco_horas_minimo`/`banco_horas_consumo`
do enum de `contratos_fornecedor.tipo`. Verificado antes: só 1 linha
existia na tabela (tipo `mensalidade_fixa`, rascunho de teste) — sem
risco de perda de dado.

```sql
ALTER TABLE contratos_fornecedor
    MODIFY COLUMN tipo ENUM('mensalidade_fixa','hora_aberta') NOT NULL;
```

As colunas `horas_banco`, `horas_minimas` e `valor_hora_excedente`
ficaram sem uso (não foram removidas — `DROP COLUMN` é destrutivo e não
há necessidade real de apagar, só de parar de gravar nelas).

---

## 2026-09-13 — Categoria de fornecedor (agrupamento de relatório)

Campo livre (não enum) pra agrupar fornecedores em relatórios de custo
— Funcionário, Terceirizado, Contabilidade, Imposto, Software, etc.
Texto livre em vez de enum de propósito: essas categorias evoluem com
frequência e um enum exigiria `ALTER TABLE` toda vez que surgisse uma
categoria nova — o formulário sugere valores comuns via `<datalist>`,
mas aceita qualquer texto. Diferente de `fornecedores.tipo`
(operacional/fixo), que é sobre vínculo com Movidesk, não sobre
classificação contábil.

```sql
ALTER TABLE fornecedores ADD COLUMN categoria VARCHAR(50) NULL AFTER tipo;
```

Coluna nullable, sem risco pros fornecedores já cadastrados (viram
`categoria = NULL`, tratado como "Sem categoria" nos relatórios até
serem editados).

---

## 2026-09-22 — Faturamento manual (receita de parceiros fora do ASAAS)

HubVision e MobCode pagam a Newsiga pelo repasse de horas do Robson,
mas não são clientes cobrados via ASAAS — a receita deles precisa
entrar no CRM (pra bater com a despesa/repasse que já existe do lado
do Robson), mas o lançamento é sempre manual, nunca automático.

```sql
ALTER TABLE contratos
    MODIFY COLUMN faturamento_gerenciado_por enum('sistema','assinatura_asaas','manual') NOT NULL DEFAULT 'sistema';
```

`fechar-competencia.php` já filtra `faturamento_gerenciado_por =
'sistema'` em ambas as queries — um contrato `'manual'` fica
automaticamente fora do fechamento automático, sem precisar mexer
nesse arquivo. Faturas lançadas manualmente (`salvar-receita-manual.php`)
nascem com `status = 'confirmado'` (valor do enum de `faturas.status`
que já existia mas nunca era usado por nenhum fluxo — reservado pra
esse caso) e `asaas_payment_id = NULL`, que é como o resto do sistema
já distingue implicitamente uma fatura sem cobrança real associada.

Nenhuma coluna nova em `faturas` — reaproveita a estrutura existente.

---

## 2026-09-12 — Exceção de taxa por cliente em contratos_fornecedor

Um fornecedor `hora_aberta` normalmente cobra a mesma taxa de qualquer
cliente que atende (um único contrato "regra geral" cobre todo mundo).
Alguns fornecedores (ex: Robson Augusto) têm uma taxa diferente pra um
cliente específico (Tron R$50/h vs. R$70/h dos demais) — sem precisar
cadastrar um contrato por cliente pra todo mundo, só quem realmente tem
exceção ganha um contrato adicional vinculado ao cliente.

```sql
ALTER TABLE contratos_fornecedor
    ADD COLUMN cliente_id INT UNSIGNED NULL AFTER fornecedor_id,
    ADD CONSTRAINT fk_contratos_fornecedor_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id);
```

**Atenção pra próxima vez**: a primeira tentativa usou `INT NULL` (sem
`UNSIGNED`) e falhou silenciosamente — `clientes.id` é `INT(10)
UNSIGNED`, e o MySQL recusa criar uma FK quando os tipos não batem
exatamente (assinado × não assinado). Sempre conferir `SHOW CREATE
TABLE` da tabela referenciada antes de escrever uma FK nova.

Coluna nullable, adicionada sem risco (as 13 linhas já existentes viram
`cliente_id = NULL`, que é o valor correto — "regra geral" — pra todas
elas). Também foi criada `atualizar-contrato-fornecedor.php` (edição de
contrato, que não existia até então).

---

## 2026-10-02 — Fechamento de consultores (adicional/observação na despesa + vínculos com o Movidesk)

Contexto completo em `docs/fechamento-consultores-crm.md`. Uma mudança
de schema (só acrescenta colunas) e três acertos de dados, todos
necessários pra tela `crm-newsiga-fechamento-consultores.php`.

**Ordem obrigatória:** aplicar este SQL ANTES do deploy do código — o
`listar-despesas-competencia.php` novo já seleciona as duas colunas, e
a tela de Despesas quebra se o código subir primeiro.

Backup antes: exportar `despesas_competencia`, `clientes`,
`contratos_fornecedor` e `fornecedores` (phpMyAdmin → Exportar).

```sql
-- 1. Schema: valor adicional (despesa, comissão, participação em
--    projeto) e observação com o motivo. `valor` continua sendo o total.
ALTER TABLE despesas_competencia
    ADD COLUMN valor_adicional DECIMAL(10,2) NULL AFTER valor,
    ADD COLUMN observacao VARCHAR(255) NULL AFTER origem;

-- 2. Cliente do CRM -> nome da organização no Movidesk. A coluna
--    clientes.movidesk_organization já existia, vazia e sem uso.
--    Os três hotéis são uma organização só no Movidesk.
UPDATE clientes SET movidesk_organization = 'Dragão'           WHERE id = 5;
UPDATE clientes SET movidesk_organization = 'Fiabesa'          WHERE id = 10;
UPDATE clientes SET movidesk_organization = 'Ocaporã'          WHERE id IN (6, 7, 8);
UPDATE clientes SET movidesk_organization = 'Becker'           WHERE id = 2;
UPDATE clientes SET movidesk_organization = 'Mari Louças'      WHERE id = 1;
UPDATE clientes SET movidesk_organization = 'Noronha Pescados' WHERE id = 3;
UPDATE clientes SET movidesk_organization = 'Tron Soluções'    WHERE id = 9;

-- 3. Contratos do Robson -> cliente. Sem isso todos contam como "regra
--    geral" e o cálculo não sabe qual taxa usar.
UPDATE contratos_fornecedor SET cliente_id = 9, valor_hora = 70.00 WHERE id = 4; -- Tron (taxa subiu de R$ 50 pra R$ 70 a pedido do Felipe)
UPDATE contratos_fornecedor SET cliente_id = 8  WHERE id = 5;        -- Ocaporã, fixo
UPDATE contratos_fornecedor SET cliente_id = 2  WHERE id = 6;        -- Becker, fixo
UPDATE contratos_fornecedor SET cliente_id = 12 WHERE id = 9;        -- MobCode
UPDATE contratos_fornecedor SET cliente_id = 11 WHERE id IN (10, 11); -- HubVision

-- 4. Fornecedor -> consultor no Movidesk. A Prudencial (contabilidade
--    do Bruno Silva) estava vinculada ao Robson por engano.
UPDATE fornecedores SET movidesk_technician_name = NULL              WHERE id = 7;
UPDATE fornecedores SET movidesk_technician_name = 'Luis Felipe'     WHERE id = 3;
UPDATE fornecedores SET movidesk_technician_name = 'Normando Junior' WHERE id = 1;
```

Pernambuco Química, HubVision e MobCode ficam sem organização: a
primeira não teve apontamento com nome de organização identificável de
julho a setembro/2026, e as horas das outras duas vêm do sistema dos
parceiros, não do Movidesk (por isso os contratos do Robson pra elas
continuam com valor digitado à mão).

**Aplicação:** em 02/10/2026, pelo Felipe, via phpMyAdmin — a gravação
em produção por SSH é bloqueada pelas permissões da sessão do Claude
Code (leitura é liberada). Conferido depois por consulta: as duas
colunas criadas e os 18 vínculos preenchidos. Na primeira tentativa só
o `ALTER` teve efeito; os `UPDATE` foram rodados de novo, sem os
comentários entre eles.

---

## 2026-10-09 — Contrato de suporte da Dragão passa a mensalidade fixa

Acerto de dado, sem mudança de schema. A Dragão renegociou o suporte
(proposta PRP-2026-0116, contrato CTR-2026-0106 Rev. 02): sai o pacote
de 8h com excedente e entra valor fixo mensal de R$ 5.000,00 + imposto,
a partir da competência 2026-10.

```sql
-- Antes: tipo = 'banco_horas_minimo', valor = 1440.00, valor_hora = 230.00,
--        horas_minimas = 8.00, origem_proposta = 'PRP-2026-SUPORTE-0112'
UPDATE contratos
SET tipo = 'mensalidade_fixa', valor = 5882.35, valor_hora = NULL,
    horas_minimas = NULL, origem_proposta = 'PRP-2026-SUPORTE-0116'
WHERE id = 11 AND cliente_id = 5 AND tipo = 'banco_horas_minimo';
```

O valor gravado é o total com imposto (5.000,00 / 0,85): em
`mensalidade_fixa` o `fechar-competencia.php` não aplica o imposto por
fora. O tipo foi trocado no próprio contrato, e não encerrando e criando
outro como sugere o `atualizar-contrato.php`, porque é o mesmo contrato
revisado e as faturas já geradas (2026-08 e 2026-09) guardam valor e
composição próprios. `movidesk_contract_name` ficou como estava
("Suporte Dragão"), sem uso no fechamento desse tipo.

**Aplicação:** em 09/10/2026, por SSH. Conferido com
`php crm/fechar-competencia.php 2026-10 11 --simular`: geraria
R$ 5.882,35 com vencimento em 10/11/2026.

---

## 2026-10-22 — Contrato de suporte da Mari Louças passa a mensalidade fixa

Acerto de dado, sem mudança de schema. O suporte da Mari Louças deixa de
ser cobrado por hora (R$ 200,00/h) e passa a valor fechado de
R$ 7.000,00 + imposto, a partir da competência 2026-10 (cobrança emitida
no início de novembro, vencimento dia 5).

Conferido por leitura em 22/10/2026: o contrato de suporte do cliente 1
é o #18 e ainda estava como `hora_aberta`.

```sql
-- Antes: tipo = 'hora_aberta', valor = NULL, valor_hora = 200.00,
--        valor_hora_excedente, horas_banco e horas_minimas = NULL,
--        origem_proposta = 'CTR-2026-0104_SUPORTE_Mari_Loucas'
UPDATE contratos
SET tipo = 'mensalidade_fixa', valor = 8235.29, valor_hora = NULL,
    valor_hora_excedente = NULL, horas_banco = NULL, horas_minimas = NULL
WHERE id = 18 AND cliente_id = 1 AND tipo = 'hora_aberta';
```

Mesma regra da Dragão: o valor gravado é o total com imposto
(7.000,00 / 0,85), porque em `mensalidade_fixa` o `fechar-competencia.php`
não aplica o imposto por fora; e o tipo foi trocado no próprio contrato,
sem encerrar e criar outro, para preservar as faturas já geradas
(2026-08 e 2026-09). `origem_proposta` e `movidesk_contract_name`
("Suporte Mari Louças") ficam como estão — não há proposta nova
registrada, e o nome do Movidesk não é usado no fechamento desse tipo.

**Limite de desenvolvimento:** o contrato prevê até 40h mensais de
desenvolvimento. A mensalidade fixa não controla esse limite: se houver
excedente em algum mês, ele será lançado à mão como receita avulsa.

**Aplicação:** em 22/10/2026, pelo Felipe, via phpMyAdmin (1 linha
afetada). Conferido por consulta (contrato #18 como `mensalidade_fixa`,
valor 8235.29, campos de hora nulos) e com
`php crm/fechar-competencia.php 2026-10 18 --simular`: geraria
R$ 8.235,29 com vencimento em 05/11/2026.
