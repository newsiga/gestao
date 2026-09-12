# newsiga/gestao — guia de desenvolvimento

Este repositório reúne os sistemas de gestão da Newsiga: CRM (`crm/`), área do cliente e site institucional (`site/`). O desenvolvimento é feito via Claude Code, com deploy manual pra Hostgator (ver `docs/deploy.md`).

Este arquivo reúne práticas que valem para **qualquer** funcionalidade nova, não só uma específica — o Claude Code deveria ler isto antes de começar a trabalhar em qualquer parte do projeto.

## 1. Explorar antes de implementar

Todo o código já existente (CRM, área do cliente) foi construído em sessões anteriores, sem o contexto desta conversa atual. Isso significa que qualquer plano técnico (os documentos em `docs/*-crm.md`, por exemplo) descreve uma **intenção arquitetural**, não necessariamente o código real.

Antes de escrever qualquer coisa nova:
- Ler os arquivos existentes que fazem algo parecido com o que será construído, e seguir a mesma convenção (nomes de variável, formato de resposta, tratamento de erro) em vez de introduzir um estilo próprio.
- Ler a estrutura real de tabelas relacionadas (via `SHOW CREATE TABLE` ou inspecionando os arquivos de cadastro/salvar existentes) antes de assumir nomes de campo — nunca assumir que um schema proposto num documento de planejamento bate exatamente com o banco real.
- Se a convenção real divergir do que um documento de briefing sugere, **a convenção real do código prevalece**. Os documentos em `docs/` são ponto de partida, não especificação rígida.

## 2. Segurança e credenciais

- Nenhum arquivo de configuração com credencial real (banco de dados, tokens de API) é versionado. Cada um desses arquivos tem uma cópia real só no servidor (fora do Git) e, quando fizer sentido, um `.example.php` versionado com valores fictícios — ver `docs/deploy.md`, seção "Arquivos de configuração".
- Antes de criar qualquer arquivo novo que vá guardar uma chave de API, senha, ou token: primeiro adicionar o caminho ao `.gitignore`, depois criar o arquivo — nunca a ordem inversa.
- Revisar o `git diff` (ou a lista de arquivos no commit, na interface do VS Code) antes de cada commit, especialmente depois de mexer em algo relacionado a autenticação ou integração externa — é fácil um valor real entrar sem querer.
- Se uma credencial for commitada por engano: trocar/revogar a credencial na origem (ASAAS, Movidesk, Google Cloud, banco), não só remover do arquivo — o valor continua existindo no histórico do Git mesmo depois de "apagado" num commit posterior.

## 3. Mudanças no banco de dados

Não existe uma ferramenta de migração automatizada neste projeto — mudanças de schema são aplicadas manualmente. Pra evitar perder o controle de qual alteração já foi aplicada em produção:

- Antes de um `ALTER TABLE`/`CREATE TABLE`/`DROP` em produção, gerar um backup pontual da tabela afetada (`mysqldump` daquela tabela específica, ou export via phpMyAdmin).
- Registrar cada mudança de schema aplicada — mesmo que informalmente, num arquivo tipo `docs/mudancas-banco.md` com data e comando executado — pra não depender de memória de qual alteração já rodou em qual ambiente.
- Nunca uma alteração destrutiva (`DROP COLUMN`, `DROP TABLE`, mudança de tipo que perde dado) sem confirmação explícita do Felipe antes de rodar.

## 4. Testando com segurança (integrações externas)

ASAAS e Movidesk são sistemas reais, com dados de clientes reais. Ao testar mudanças que envolvam essas integrações:

- Nunca gerar cobrança real, disparar e-mail real, ou alterar um chamado/contrato real como parte de um teste, sem confirmar antes que é intencional.
- Os arquivos `teste-*.php` já existentes no `crm/` sugerem que já existe um padrão de scripts de teste isolados — continuar usando esse padrão em vez de testar direto nos fluxos de produção (`crm-newsiga-*.php`).
- Ao testar o módulo de despesas/fornecedores especificamente: cuidado ao rodar contra dados reais do Movidesk pela primeira vez — validar com um fornecedor/técnico só, antes de rodar o cálculo em lote pra todos.

## 5. Deploy

Ver `docs/deploy.md` para o processo completo (varia por sistema: `crm/` via `git pull` direto, área do cliente via link simbólico, site institucional via `rsync`). Como o teste real só acontece após o deploy, isso vai fazer parte do ciclo normal de trabalho, não é um evento raro — mas cada deploy ainda é uma ação deliberada (revisar o que vai subir antes de aplicar em produção).

## 6. Git — sincronização entre duas máquinas

O trabalho acontece em duas máquinas (G15 e Yoga). Pra evitar conflito:

- Sempre `git pull` no início de uma sessão de trabalho, antes de começar a editar, em qualquer uma das duas máquinas.
- Se esquecer e um `git push` for rejeitado por haver commits novos no remoto, rodar `git pull` (que vai mesclar automaticamente, se não houver conflito no mesmo trecho de código) antes de tentar `push` de novo.
- Commits pequenos e focados, com mensagem descrevendo o que mudou — facilita entender o histórico depois, e reverter uma mudança específica se necessário, sem afetar outras.

## 7. Backup antes de mudança arriscada

Ao migrar ou reestruturar algo que já está em produção (trocar raiz de documento, criar link simbólico, mover pasta): sempre manter uma cópia do estado anterior intacta (renomear em vez de apagar) até confirmar que a mudança nova está estável — mesmo princípio já aplicado nas migrações do CRM e da área do cliente.
