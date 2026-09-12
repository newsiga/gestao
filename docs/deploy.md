# Deploy dos sistemas Newsiga

O `git push` leva o código só até o GitHub. Pra aparecer de verdade no ar, é preciso um segundo passo no servidor — e esse segundo passo varia por sistema.

## Passo comum a todos: aplicar o commit

```bash
# Na sua máquina, depois de editar código
git add .
git commit -m "descrição da mudança"
git push
```

## CRM (crm.newsiga.com.br)

O subdomínio aponta direto para `~/repos/gestao/crm`. Deploy = um `git pull`.

```bash
ssh -p 2222 hgnew010@br396.hostgator.com.br "cd repos/gestao && git pull"
```

## Área do cliente (newsiga.com.br/area-do-cliente)

`public_html/area-do-cliente` é um **link simbólico** para `~/repos/gestao/site/area-do-cliente`. Deploy = um `git pull` (o link já aponta pro conteúdo atualizado, não precisa de mais nada).

```bash
ssh -p 2222 hgnew010@br396.hostgator.com.br "cd repos/gestao && git pull"
```

## Site institucional (newsiga.com.br)

Aqui não dá pra usar link simbólico (a pasta `public_html` mistura código do site com artefatos da própria hospedagem: `cgi-bin/`, `.well-known/`, etc.). Deploy = `git pull` **+** `rsync` copiando só os arquivos do site.

```bash
ssh -p 2222 hgnew010@br396.hostgator.com.br "cd repos/gestao && git pull && rsync -av site/institucional/ ~/public_html/"
```

## Passo a passo interativo (se preferir, em vez do comando de uma linha)

1. `ssh -p 2222 hgnew010@br396.hostgator.com.br`
2. `cd repos/gestao`
3. `git pull`
4. Se for o site institucional: `rsync -av site/institucional/ ~/public_html/`
5. `exit`

## Dados de acesso

- Host: `br396.hostgator.com.br`
- Porta SSH: `2222`
- Usuário: `hgnew010`
- Repositório: `git@github.com:newsiga/gestao.git`
- Pasta de deploy no servidor: `~/repos/gestao`

## Mapa de pastas do repositório

```
gestao/
├── crm/                     → crm.newsiga.com.br (aponta direto)
├── site/
│   ├── area-do-cliente/     → newsiga.com.br/area-do-cliente (link simbólico)
│   └── institucional/       → newsiga.com.br (copiado via rsync)
└── docs/                    → esta documentação
```

## Arquivos de configuração com credenciais (nunca versionados)

Ficam só no servidor, fora do Git (listados no `.gitignore`):

| Sistema | Arquivo no servidor |
|---|---|
| CRM | `~/repos/gestao/config-crm.php` |
| Área do cliente (banco) | `~/repos/gestao/site/area-do-cliente/config.php` |
| Área do cliente (Movidesk) | `~/repos/gestao/private/newsiga-movidesk.php` |

Se algum desses "sumir" depois de um `git pull` num clone novo, é porque nunca foram versionados de propósito — precisam ser copiados manualmente uma vez (a partir da cópia original que já existe em outro lugar do servidor) pra dentro do clone de deploy.

## Nota técnica: caminhos relativos e links simbólicos

Arquivos de config referenciados via caminho relativo (`__DIR__ . '/../config.php'` ou similar) calculam esse caminho a partir da localização **real** do arquivo — que muda quando o arquivo é acessado através de um link simbólico. Ao migrar mais alguma coisa desse jeito, sempre conferir se algum `require`/`include` usa caminho relativo antes de trocar a pasta ao vivo por um link.

## Deploy como parte do ciclo de desenvolvimento

Diferente de um ajuste pontual, o desenvolvimento ativo (com o Claude Code) normalmente exige testar cada mudança no ambiente real — não dá pra validar só localmente. Nesse contexto, o deploy deixa de ser um evento raro e passa a acontecer várias vezes ao longo de uma sessão de trabalho. Pode pedir pro Claude Code rodar o passo de deploy (comandos acima) sempre que quiser ver o resultado de uma alteração, sem tratar isso como algo excepcional.

Se esse ritmo ficar frequente o suficiente para incomodar, o próximo passo natural é automatizar via GitHub Action (deploy automático a cada `push` na branch principal) — elimina até o comando manual de SSH. Não é necessário agora, mas fica registrado como evolução natural quando o fluxo atual pesar.

## Pastas antigas mantidas como backup

- `~/crm.newsiga.com.br/` (versão anterior à migração)
- `~/public_html/area-do-cliente-backup-antigo/`

Podem ser removidas quando o novo fluxo estiver validado por mais tempo.
