# Deploy do CRM Newsiga

O `git push` leva o código só até o GitHub. Pra ele aparecer de verdade em `crm.newsiga.com.br`, é preciso um segundo passo: puxar as atualizações no servidor.

## Fluxo completo

```bash
# 1. Na sua máquina, depois de editar código
git add .
git commit -m "descrição da mudança"
git push

# 2. Aplicar no servidor (deploy)
ssh -p 2222 hgnew010@br396.hostgator.com.br "cd repos/gestao && git pull"
```

## Passo a passo do deploy (se preferir sessão interativa)

1. `ssh -p 2222 hgnew010@br396.hostgator.com.br`
2. `cd repos/gestao`
3. `git pull`
4. `exit`

## Dados de acesso

- Host: `br396.hostgator.com.br`
- Porta SSH: `2222`
- Usuário: `hgnew010`
- Repositório: `git@github.com:newsiga/gestao.git`
- Pasta de deploy no servidor: `~/repos/gestao`
- Subdomínio `crm.newsiga.com.br` aponta para: `repos/gestao/crm`

## Notas

- O `config-crm.php` (credenciais do banco/APIs) **não** está no Git — vive só no servidor, em `~/repos/gestao/config-crm.php`, referenciado por `crm/db.php` via `__DIR__ . '/../config-crm.php'`.
- A pasta antiga `~/crm.newsiga.com.br/` foi mantida como backup após a migração — pode ser removida quando o novo fluxo estiver validado por um tempo.
- Site institucional e área do cliente (`newsiga.com.br`) ainda não passaram por essa migração — continuam fora do Git por enquanto.
