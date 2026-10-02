# Fechamento de consultores (contas a pagar semi-automático) — CRM Newsiga

Plano combinado com o Felipe em 01/10/2026 e **implementado em 02/10/2026**. Complementa o `docs/despesas-fornecedores-crm.md` (a "fase 2" que lá aparece como cálculo automático via Movidesk).

## Estado em 02/10/2026

No ar: tela `crm/crm-newsiga-fechamento-consultores.php` (botão na tela de Despesas), cálculo em `crm/calculo-fechamento-consultores.php`, prévia em `crm/previa-fechamento-consultores.php`, lançamento em lote em `crm/lancar-fechamento-consultores.php`. A regra de cálculo está descrita no cabeçalho do arquivo de cálculo.

O cálculo foi conferido com setembro/2026 por linha de comando. A tela e o lançamento em lote **ainda não foram usados no navegador** — o primeiro uso real é o fechamento de setembro, pelo Felipe.

O que saiu diferente do plano abaixo:

- **Só uma mudança de schema.** `clientes.movidesk_organization` já existia, vazia; só foram acrescentadas `valor_adicional` e `observacao` em `despesas_competencia` (ver `docs/mudancas-banco.md`, 02/10/2026).
- **Tron passou a R$ 70/h** (era R$ 50) a pedido do Felipe em 02/10. Como ficou igual à regra geral, o Felipe concluiu o contrato "Valor hora Tron Soluções" (#4) e as horas da Tron caem na regra geral do Robson. O Robson de setembro dá R$ 6.181,33, o mesmo valor da planilha.
- **Departamentos viram a empresa.** No Movidesk a Noronha (e a Pernambuco Química) separam os solicitantes em departamentos — pessoas do tipo 4, vinculadas à empresa. O cálculo troca o departamento pela empresa (`movidesk_empresa_dos_departamentos()`, uma consulta a mais por prévia).
- **Mais de um adicional por despesa**, cada um com valor e motivo. No banco ficam somados em `valor_adicional`; com mais de um, a `observacao` lista o valor de cada um.
- **A tela foi redesenhada** depois da primeira versão (tabela larga): um cartão por fornecedor, horas por cliente em etiquetas, adicional recolhido, barra fixa com o total.
- **Um lançamento por contrato**, como já era. O Robson sai em várias linhas (Tron, regra geral, fixos).
- **Toda hora apontada é paga**, inclusive em demanda interna ("Financeiro", "Contabilidade"...) — confirmado pelo Felipe. Caem na regra geral do consultor.
- **Prudencial Contabilidade é a empresa do Bruno Silva** (contador da Newsiga, que às vezes atua como consultor). O vínculo dela com "Robson Augusto" no Movidesk era engano e foi removido. O Bruno não aparece no Movidesk como consultor (julho a setembro/2026): as horas dele são digitadas na tela.
- **A tela lista todos os contratos de fornecedor ativos**, não só consultores, com o valor do contrato como sugestão.
- **Lançamento vindo do fechamento pode ser excluído** enquanto não estiver pago (antes só o manual podia) — é o jeito de corrigir: exclui e lança de novo pela tela.

Ficou para depois:

- Campo na tela de cliente para `movidesk_organization` (hoje só por SQL). Só faz falta para cliente com taxa própria ou fixo de algum consultor.
- O Felipe aparece num aviso da tela todo mês (aponta horas no Movidesk e não é fornecedor).
- Adicionais recorrentes (fim de semana, presencial) continuam manuais.

O restante deste documento é o plano original, mantido como registro.

Como todo documento desta pasta, é ponto de partida: se o código real divergir do que está aqui, o código prevalece (ver `README.md`, seção 1).

## Objetivo

Hoje o Felipe apura o pagamento dos consultores numa planilha, mês a mês, e lança tudo à mão no CRM. A ideia é o CRM calcular a maior parte a partir das horas do Movidesk e o Felipe só revisar, ajustar exceções e confirmar.

**Decisão do Felipe: semi-automático.** O CRM propõe os valores; nada é lançado sem ele revisar, porque todo mês há pequenas particularidades (despesa, comissão, participação em projeto).

## Prazo que motivou

Os pagamentos da competência 2026-09 vencem em **10/10/2026** e, em 01/10, só a despesa do Movidesk estava lançada para setembro. Se a tela não ficar pronta antes, setembro é lançado à mão como agosto foi — os valores já apurados estão na seção "Validação".

## Situação atual do módulo de despesas

- Cadastro pronto: `fornecedores`, `contratos_fornecedor` (tipos `mensalidade_fixa` e `hora_aberta`), `despesas_competencia`.
- Todo lançamento é manual (`crm/crm-newsiga-lancar-despesa.php` → `crm/salvar-despesa-competencia.php`), com `origem = 'manual'`. O valor `movidesk_automatico` do enum nunca foi usado.
- Só existe **um lançamento por contrato de fornecedor por competência** (`salvar-despesa-competencia.php` recusa o segundo).
- `fornecedores.movidesk_technician_name` já guarda o nome do consultor no Movidesk, mas nada lê esse campo para calcular.

## Como ficaria

Tela nova "Fechamento de consultores", com seletor de competência:

- **Consultor por hora:** horas do Movidesk separadas por cliente, taxa do contrato aplicada, valor sugerido.
- **Consultor fixo:** valor do contrato; as horas aparecem só como informação.
- **Ajuste por linha:** valor adicional + observação.
- **Lançar:** o Felipe marca as linhas e confirma; elas viram `despesas_competencia` com status `a_pagar` e `origem = 'movidesk_automatico'`, aparecendo na tela de Despesas que já existe. Linha já lançada para a competência aparece como tal, sem duplicar.

## Regras de negócio confirmadas pelo Felipe

- **Hora coberta por fixo não é paga por hora.** As horas do Robson na Ocaporã e na Becker não entram no cálculo por hora, porque estão dentro dos fixos (R$ 2.400 e R$ 1.000).
- **Taxa por cliente.** O Robson recebe R$ 50/h nas horas da Tron e R$ 70/h nos demais clientes (regra geral).
- **HubVision e MobCode ficam manuais.** O Robson é terceirizado para essas consultorias parceiras; as horas e os valores vêm do sistema delas, não do Movidesk da Newsiga.
- **Adicionais são pontuais, lançados à mão.** Exemplos reais de setembro: Luis Felipe + R$ 40 (1h de atendimento à Mari Louças no fim de semana) + R$ 100 (despesa de atendimento presencial na Fiabesa); Normando + R$ 1.000 (participação em projeto na Fiabesa).
- **Zeramento comercial não afeta o consultor.** Quando o valor de um ticket é zerado no Movidesk por acordo com o cliente (caso do ticket 9452, Dragão), as horas continuam valendo para pagar o consultor. O cálculo aqui usa as horas apontadas, nunca o valor cobrado do cliente.

## Validação já feita (setembro/2026, só leitura)

Horas por consultor obtidas direto do Movidesk, comparadas com a coluna de outubro da planilha do Felipe:

| Consultor | Horas | Cálculo | Planilha |
|---|---|---|---|
| Rodrigo Feitosa | 41,52h | × R$ 70 = R$ 2.906,40 | R$ 2.906 |
| Fernando Lins | 24,32h | × R$ 50 = R$ 1.216,00 | R$ 1.216 |
| Robson Augusto | 48,95h | 1,60h Tron × R$ 50 + 38,13h (Dragão 35,65 + Mari Louças 2,48) × R$ 70 + fixos R$ 3.400 = R$ 6.149,10 | R$ 6.181 |
| Luis Felipe | 39,55h | fixo R$ 5.000 + R$ 140 de adicionais | R$ 5.140 |
| Normando Junior | 9,42h | fixo R$ 14.000 + R$ 1.000 de adicional | R$ 15.000 |

A diferença de R$ 32 do Robson é erro da planilha (horas da Tron calculadas a R$ 70 em vez de R$ 50) — o valor correto é R$ 6.149,10.

Horas do Robson não pagas por hora em setembro: Ocaporã 4,67h e Becker 4,55h.

### Consulta usada

API de tickets do Movidesk (via `movidesk_get`), 119 tickets em 2 páginas:

- `$filter`: `actions/any(a: a/timeAppointments/any(t: t/date ge 2026-09-01T00:00:00.00z and t/date lt 2026-10-01T00:00:00.00z))`
- `$expand`: `clients($select=businessName;$expand=organization($select=businessName)),actions($select=id;$expand=timeAppointments($expand=createdBy($select=businessName)))`
- `$top=100` com `$skip`, e pausa de alguns segundos entre páginas (limite de requisições do Movidesk).
- O filtro traz o ticket inteiro: é preciso descartar no código os apontamentos de outros meses.
- Consultor = `createdBy.businessName` do apontamento. Cliente = organização do primeiro cliente do ticket. Horas = `movidesk_horas_apontamento()`, a mesma função do fechamento de receita.

## O que precisa ser construído

### Banco (duas mudanças que só acrescentam colunas — aguardam aprovação do Felipe)

1. **Vínculo cliente do CRM ↔ organização no Movidesk.** Os nomes não batem ("Hotel Ocaporã" × "Ocaporã", "Indústrias Becker" × "Becker", "Tron Soluções Tecnológicas LTDA" × "Tron Soluções"), e o grupo Ocaporã são três clientes no CRM para uma organização só no Movidesk. Proposta: coluna nova em `clientes` com o nome da organização no Movidesk. Sem isso não dá para aplicar taxa por cliente nem excluir horas cobertas por fixo.
2. **Adicional e observação em `despesas_competencia`.** Hoje só existe `valor`; o motivo do adicional se perderia.

Seguir o `README.md`, seção 3: backup da tabela antes e registro em `docs/mudancas-banco.md`.

### Dados

- Preencher `cliente_id` nos contratos do Robson: #4 (Tron, R$ 50/h), #5 (Ocaporã, fixo), #6 (Becker, fixo). A coluna existe desde 12/09/2026, mas os três estão com `cliente_id` nulo.
- Preencher o vínculo com o Movidesk nos clientes.

### Código

- Função em `crm/movidesk-client.php` para horas por consultor e cliente numa competência (a consulta acima).
- Endpoint de prévia (somente leitura) e endpoint de lançamento em lote.
- Tela nova, no padrão visual das telas de despesa existentes, e link a partir de `crm/crm-newsiga-despesas.php`.

## Pontos em aberto

- **Um lançamento por contrato por competência:** com a taxa por cliente, um consultor pode ter mais de um contrato por hora (Robson: #4 Tron e #15 regra geral). Decidir se o lançamento sai um por contrato (como hoje) ou um por consultor.
- **Organizações que não são clientes:** em setembro apareceram "Financeiro", "Contabilidade", "Comercial", "Faturamento", "Produção", "PCP", "Recebimento". O Fernando Lins tem 8,27h em "Financeiro" e elas entraram no cálculo que bateu com a planilha — ou seja, hoje são pagas. Confirmar a regra com o Felipe.
- **Consultor sem vínculo com o Movidesk:** Bruno Silva (R$ 60/h) não tem `movidesk_technician_name` e não apareceu na consulta de setembro; Ellen Silva tem contrato ainda em rascunho. Confirmar de onde vêm as horas deles.
- **Cadastro inconsistente:** o fornecedor "Prudencial Contabilidade" está com `movidesk_technician_name = 'Robson Augusto'`, provavelmente por engano — corrigir antes, senão as horas do Robson seriam atribuídas a dois fornecedores.
- **Felipe Valença** aparece no Movidesk com 25,33h em setembro; é o sócio, não entra no contas a pagar.
- **Adicional recorrente:** os R$ 40 de fim de semana do Luis Felipe podem virar regra automática no futuro (o Movidesk já classifica o tipo de hora); por ora ficam manuais.
