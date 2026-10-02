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
