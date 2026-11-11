# Conector MCP do CRM (fluxo de caixa no Claude)

Servidor MCP remoto que deixa o Claude (web, desktop e app no celular, inclusive por voz) consultar o fluxo de caixa da Newsiga direto do banco do CRM. **Somente leitura**, uso exclusivo do Felipe.

- Endereço do conector: `https://crm.newsiga.com.br/mcp`
- Código: `crm/mcp-conector/`, publicado pelas regras de `crm/.htaccess`
- Tabelas próprias: `docs/conector-mcp-schema.sql`, em um banco separado do CRM

## Tools

| Tool | Responde |
|---|---|
| `resumo_mes(mes?)` | Receita prevista e recebida, despesa prevista e paga, saldo projetado e realizado do mês, com os itens |
| `despesas_a_vencer(ate_data?)` | Despesas não pagas até a data (inclui atrasadas e fixos de fornecedor ainda não lançados) |
| `receitas_a_receber(ate_data?)` | Cobranças em aberto por cliente, com status no ASAAS consultado na hora |
| `saldo_disponivel_para_investir(mes?, valor?)` | Quanto sobra no mês, em leitura conservadora e projetada; com `valor`, diz se cabe |
| `inadimplencia()` | Cobranças vencidas e não pagas, com total por cliente |

`mes` no formato `AAAA-MM` e `ate_data` em `AAAA-MM-DD`. Sem parâmetro, vale o mês atual ou o último dia dele. Os valores vêm em reais, e as datas em ISO, no fuso America/Recife.

## Adicionar no Claude

1. No claude.ai, abra **Configurações → Conectores → Adicionar conector personalizado**.
2. Nome: `CRM Newsiga`. URL: `https://crm.newsiga.com.br/mcp`. Deixe os campos de OAuth avançados em branco.
3. Clique em **Conectar**. Abre a tela do CRM: entre com o usuário e a senha do CRM e clique em **Autorizar**.
4. O conector aparece também no app do celular, sem configurar de novo. Numa conversa, ative-o no menu de ferramentas e pergunte, por exemplo: "como está o caixa deste mês?" ou "posso aplicar 10 mil?".

O acesso dura enquanto o Claude renovar o token (a cada hora, com validade de 30 dias sem uso). Se pedir para conectar de novo, é só repetir o passo 3.

## Instalação no servidor (uma vez)

A sessão do Claude Code não grava em produção; estes passos são do Felipe.

1. **Banco e usuário** (cPanel → Bancos de dados MySQL):
   - criar o banco `hgnew010_mcp`;
   - criar o usuário `hgnew010_mcp` com senha forte;
   - adicionar o usuário ao banco `hgnew010_mcp` com **SELECT, INSERT, UPDATE e DELETE**;
   - adicionar o mesmo usuário ao banco do CRM (o do `DB_NAME` no `config-crm.php`) **só com SELECT**.
2. **Tabelas**: no phpMyAdmin, selecionar o banco `hgnew010_mcp` e rodar `docs/conector-mcp-schema.sql`.
3. **Config**: acrescentar ao `~/repos/gestao/config-crm.php`:
   ```php
   define('MCP_DB_NAME', 'hgnew010_mcp');
   define('MCP_DB_USER', 'hgnew010_mcp');
   define('MCP_DB_PASS', '...');
   ```
   Sem essas constantes o conector responde 503 e fica desligado.
4. **Deploy**: `git pull` em `~/repos/gestao` (ver `docs/deploy.md`).
5. **Conferir**: `https://crm.newsiga.com.br/.well-known/oauth-protected-resource` deve devolver um JSON, e `/mcp` sem token deve responder 401.
6. **Teste com o MCP Inspector**, antes de adicionar no Claude: `npx @modelcontextprotocol/inspector`, transporte "Streamable HTTP", URL `https://crm.newsiga.com.br/mcp`, botão "Open Auth Settings" → "Quick OAuth Flow". Depois de autorizar, rodar as cinco tools.

## Segurança

- **OAuth 2.1** com PKCE S256, registro dinâmico de cliente e refresh token com rotação. Se um refresh já usado reaparecer, todos os tokens daquela autorização são revogados.
- **Quem autoriza:** só o login do CRM (`CRM_LOGIN_USUARIO`). O código de autorização só é entregue nos endereços de retorno do Claude (`claude.ai` e `claude.com`) e no localhost, usado pelo Inspector.
- **Banco:** guarda só o hash SHA-256 de tokens, códigos e segredos.
- **Somente leitura:** o usuário de banco só tem SELECT no CRM. No ASAAS, o conector tem um cliente próprio que só faz GET.
- **Limites de requisição:**

  | O quê | Limite |
  |---|---|
  | Chamadas de tool | 30 por minuto e 500 por dia por cliente |
  | Login errado | bloqueia após 5 em 15 min por IP, ou 20 por hora no total |
  | Requisições sem token | bloqueia após 60 em 10 min por IP |
  | Registros de cliente | 10 por hora por IP |

- **Log:** cada chamada vai para `mcp_log`, com data, tool, parâmetros, cliente, IP, resultado e duração.
- **Tela de autorização:** não pode ser aberta dentro de outro site (`frame-ancestors 'none'`) e tem proteção CSRF.

## Operação

```sql
-- Desligar tudo na hora: no config-crm.php, define('MCP_ATIVO', false);

-- Revogar todos os acessos (o Claude vai pedir para autorizar de novo):
UPDATE mcp_tokens SET revogado_em = NOW() WHERE revogado_em IS NULL;

-- Últimas chamadas:
SELECT criado_em, evento, tool, parametros, resultado, detalhe, duracao_ms
FROM mcp_log ORDER BY id DESC LIMIT 50;

-- Limpeza periódica (log e tokens vencidos):
DELETE FROM mcp_log WHERE criado_em < NOW() - INTERVAL 180 DAY;
DELETE FROM mcp_tokens WHERE expira_em < NOW() - INTERVAL 7 DAY;
DELETE FROM mcp_codigos WHERE expira_em < NOW() - INTERVAL 1 DAY;
```

## Regras de cálculo e limitações

- **Mês de referência:** é o do **vencimento**, como no painel e na tela de Despesas.
- **Receita prevista:** usa a mesma fórmula do painel:
  - faturas de contratos ativos;
  - parcelas de projeto;
  - mensalidades fixas ainda sem fatura, pelo valor do contrato.

  Contrato por hora sem fatura não entra: o valor só existe depois do fechamento. O resultado lista esses contratos numa observação.
- **Recebido:** o webhook do ASAAS só atualiza **parcelas**; as faturas mensais ficam como `gerado` no CRM mesmo depois de pagas. Por isso o recebido de fatura vem do ASAAS, consultado a cada chamada. Se o ASAAS não responder, vale o status do CRM e o resultado avisa.
- **Faturas divididas no ASAAS** (ex.: Dragão 2026-09): o CRM tem a fatura inteira, mas a cobrança vinculada vale só uma parte. O conector conta como recebido só o valor da cobrança vinculada e mostra o resto em aberto, com observação. A cobrança avulsa criada à mão no ASAAS não tem vínculo com o CRM e não é vista.
- **Despesa prevista:** soma os lançamentos com vencimento no mês. Do mês atual em diante, também entram os contratos **fixos** de fornecedor que ainda não têm lançamento no mês. A tela de Despesas não faz isso; aqui entra para a projeção não sair otimista.
- **Sem saldo bancário:** o CRM não tem saldo de conta. O saldo do conector é só o resultado do mês, e o "disponível para investir" não conta sobras de meses anteriores.
