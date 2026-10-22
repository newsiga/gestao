# Pendências do painel — CRM Newsiga

Dois pontos levantados em 01/10/2026, depois do primeiro fechamento mensal em lote (competência 2026-09), e deixados para desenvolver depois. Nenhum dos dois impede o fechamento nem a emissão das notas — afetam só o que o painel (`crm/crm-newsiga-painel.php`) mostra.

A ordem importa: o ponto 2 depende do ponto 1 para saber o que já foi pago.

## 1. CRM não acompanha pagamento nem atraso das faturas mensais

### Situação hoje

- O `crm/webhook-asaas.php` só trata cobranças com `externalReference` no formato `parcela:ID`. As faturas recorrentes são criadas pelo `crm/fechar-competencia.php` com `fatura:ID` e caem no trecho que ignora o evento.
- Resultado: toda fatura mensal fica com `status = 'gerado'` para sempre, mesmo depois de paga ou vencida.
- Consequências no painel:
  - O card **Em atraso** nunca mostra mensalidade atrasada — só enxerga parcela de projeto com status `atrasado`, ou cobrança que não foi gerada a tempo.
  - O **Radar de cobranças** nunca mostra "pago" para fatura mensal.

### Evidência (consulta em 01/10/2026)

As 10 faturas da competência 2026-08 (vencimento em setembro) estavam todas pagas no ASAAS (`RECEIVED` ou `RECEIVED_IN_CASH`) e todas como `gerado` no CRM.

### O que fazer

1. **Webhook:** tratar `fatura:ID` além de `parcela:ID`, com o mesmo mapa de eventos que já existe (`PAYMENT_CONFIRMED`/`PAYMENT_RECEIVED` → `pago`, `PAYMENT_OVERDUE` → `atrasado`) e o mesmo tratamento de `PAYMENT_UPDATED` (valor e vencimento). A parte de encerrar o contrato quando todas as parcelas são pagas **não** se aplica a fatura.
2. **Sincronização retroativa:** script único (padrão dos `teste-*.php`/`corrigir-*.php`: simulação por padrão, `confirmar=sim` para aplicar) que percorre faturas e parcelas com `asaas_payment_id` e status `gerado`, consulta o ASAAS (`AsaasClient::buscarCobranca`) e atualiza o status. Necessário porque os eventos das cobranças já pagas não serão reenviados.
3. **Painel:** incluir faturas com status `atrasado` no card **Em atraso** (hoje o filtro de faturas só olha `a_gerar` com vencimento passado) e conferir que o radar mostra o selo "pago" para fatura — o selo já existe para parcela.

### Pontos de atenção

- **Não precisa de mudança no banco.** `faturas.status` já é `enum('a_gerar','gerado','confirmado','pago','atrasado')` (conferido com `SHOW CREATE TABLE` em 01/10/2026).
- `confirmado` é o status das faturas de faturamento manual (`crm/salvar-receita-manual.php`), que não têm cobrança no ASAAS — não confundir com pago. Ver também `crm/atualizar-status-fatura.php`, que já altera status de fatura manualmente.
- O ASAAS tem dois status de pagamento recebido: `RECEIVED` e `RECEIVED_IN_CASH` (baixa manual). A sincronização retroativa precisa tratar os dois como pago; conferir qual evento de webhook a baixa manual dispara.
- Conferir todos os lugares que filtram `status = 'gerado'` em faturas antes de introduzir `pago`, para nenhuma soma de receita cair quando a fatura for paga (esse erro já aconteceu com parcelas — ver comentário em `crm/listar-parcelas-pendentes.php`). O `fechar-competencia.php` já trata qualquer status diferente de `a_gerar` como "já gerada", então a idempotência do fechamento não é afetada.

### Duas parcelas a investigar junto

- **Parcela #36 (Mari Louças, nº 2, venc. 10/09/2026):** paga no ASAAS em 10/09, mas `gerado` no CRM. O webhook deveria ter atualizado; não foi investigado por quê (webhook ainda não configurado na data? evento não recebido?).
- **Parcela #32 (Dragão, contrato #6, nº 8, R$ 13.976,47, venc. 10/09/2026):** está como `gerado`, mas com `asaas_payment_id` nulo. Não dá para saber pelo CRM se a cobrança existe no ASAAS nem se foi paga — conferir no painel do ASAAS e vincular.

### Duas faturas de 2026-09 parceladas direto no ASAAS

A Dragão e a Mari Louças pediram para parcelar a fatura de suporte da competência 2026-09. O parcelamento foi feito pelo Felipe só no ASAAS, sem passar pelo CRM:

- a cobrança original da fatura (criada pelo `fechar-competencia.php`, com `externalReference = fatura:ID`) foi editada para o valor e o vencimento da 1ª parcela;
- as demais parcelas foram criadas como cobranças avulsas no painel do ASAAS, **sem `externalReference`** — o CRM não sabe que elas existem;
- nenhuma nota fiscal foi cancelada nem emitida: a NFS-e continua sendo a do valor cheio, e as cobranças avulsas não geram nota.

| | Dragão | Mari Louças |
|---|---|---|
| Fatura no CRM | #24 (contrato #11) | #27 (contrato #18) |
| NFS-e | 131, de 01/10/2026 | 134, de 01/10/2026 |
| Valor da nota / da fatura no CRM | R$ 9.816,27 | R$ 15.749,76 |
| Desconto | — | R$ 695,28 (acordado R$ 15.054,48) |
| Vencimento original | 10/10/2026 | 05/10/2026 |

Cobranças no ASAAS (conferidas por leitura em 22/10/2026):

| Cliente | Parcela | Cobrança | Nº ASAAS | Valor | Vencimento | Status em 22/10 |
|---|---|---|---|---|---|---|
| Dragão | 1 | `pay_rocyq4ws5ovcf1wk` (original, `fatura:24`) | 925430360 | R$ 4.908,14 | 12/10/2026 | `RECEIVED` em 13/10 |
| Dragão | 2 | `pay_w0cxiu4xliwpdr2z` (avulsa) | 931955650 | R$ 4.908,13 | 30/10/2026 | `PENDING` |
| Mari Louças | 1 | `pay_zmff3z5kc1y708zp` (original, `fatura:27`) | 925430416 | R$ 9.411,76 | 22/10/2026 | `RECEIVED_IN_CASH` em 22/10 |
| Mari Louças | 2 | `pay_cd0zhpr66fjsf1mk` (avulsa) | 943921699 | R$ 2.821,36 | 05/11/2026 | `PENDING` |
| Mari Louças | 3 | `pay_j4tozjad6t5kmz7o` (avulsa) | 943923155 | R$ 2.821,36 | 26/11/2026 | `PENDING` |

**Valor e vencimento no CRM continuam os originais.** O webhook ignora qualquer evento de cobrança `fatura:ID` (só trata `parcela:ID`), inclusive o `PAYMENT_UPDATED` disparado pela edição da cobrança original. Por isso as faturas #24 e #27 estão com o valor cheio, o vencimento original e `status = 'gerado'`. Para a receita da competência o valor cheio está certo, porque é o valor da nota — não ajustar.

**Ao desenvolver este ponto 1:**

- A sincronização retroativa (item 2 de "O que fazer") **não pode marcar as faturas #24 e #27 como pagas** só porque a cobrança original está `RECEIVED`/`RECEIVED_IN_CASH`: ela é só a 1ª parcela, e as outras são as cobranças avulsas acima, que o CRM não conhece. Excluir as duas da sincronização (ou tratá-las à mão) e só dar baixa depois que todas as parcelas forem pagas — Dragão em 30/10, Mari Louças em 26/11, se nada mudar.
- O mesmo vale para o webhook: `PAYMENT_RECEIVED` da cobrança original de uma fatura parcelada não pode virar `pago` na fatura.
- Se o webhook passar a tratar `PAYMENT_UPDATED` de fatura do mesmo jeito que trata de parcela (item 1 de "O que fazer"), uma edição como esta gravaria o valor da 1ª parcela em `faturas.valor` e derrubaria a receita da competência. Fatura é o valor da nota; antes de copiar o tratamento das parcelas, decidir se `PAYMENT_UPDATED` deve atualizar só o vencimento, ou nada.

**Pendente — NFS-e 134 da Mari Louças:** o contador ainda vai decidir o tratamento da nota por causa do desconto de R$ 695,28. Se a nota for reemitida pelo valor com desconto (R$ 15.054,48), a fatura #27 precisa ser ajustada no CRM (`valor`, `valor_liquido` e `valor_imposto`). Se a nota ficar como está, a fatura também fica.

## 2. Card "A gerar essa semana" fica sempre em zero

### Situação hoje

O card soma faturas e parcelas com status `a_gerar` e vencimento nos próximos 7 dias — ou seja, cobranças que ainda **não foram criadas** no ASAAS. Com o fechamento automático gerando tudo no dia 1º, quase nunca sobra nada nessa condição: o card só mostraria algo se uma geração falhasse.

### O que fazer

Trocar por **"A receber essa semana"**: soma do que vence nos próximos 7 dias e ainda não foi pago (faturas + parcelas), com a contagem de cobranças embaixo.

- Em 01/10/2026 esse card mostraria R$ 59.450,80 em 8 cobranças (vencimentos de 05 e 08/10).
- Depende do ponto 1: sem o status `pago` nas faturas, o card contaria como "a receber" o que já foi pago.

### Pontos de atenção

- O alerta de "cobrança não gerada" não se perde: o card **Em atraso** já cobre fatura/parcela `a_gerar` com vencimento passado.
- O painel calcula "hoje" em UTC (`hojeISO()` usa `toISOString()`), então depois das 21h ele já considera o dia seguinte. Vale corrigir junto, já que esse card e o **Em atraso** dependem da data. Observado só na leitura do código, não testado.
- O card de receita da tela de Despesas (`crm/crm-newsiga-despesas.php`) usa a mesma fórmula de "Previsão do mês" do painel; se a regra de status mudar em um, conferir o outro.
