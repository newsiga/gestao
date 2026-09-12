# Módulo de Despesas/Fornecedores — CRM Newsiga

Objetivo: expandir o `crm.newsiga.com.br` (hoje só receita, via ASAAS/Movidesk) para cobrir também despesas/fornecedores, permitindo fluxo de caixa projetado real (receita prevista − despesa prevista) sem depender de planilha manual.

## Antes de implementar: reconhecimento do projeto existente

Este CRM foi construído em sessões anteriores, sem o contexto de código deste chat — este documento descreve a arquitetura desejada, mas não o código real já existente. Antes de escrever qualquer linha nova, ler e entender:

- `crm/asaas-client.php` e `crm/movidesk-client.php` — como as integrações existentes já se autenticam, tratam erro e formatam resposta. O módulo novo deve seguir o mesmo padrão, não inventar um estilo próprio.
- `crm/crm-newsiga-cadastro-contrato.php` e `crm/salvar-contrato.php` — a estrutura real da tabela de contratos de cliente (nomes de campo, enum de tipos de contrato, como o cálculo de hora excedente/banco de horas é feito hoje). A modelagem sugerida abaixo é conceitual; os nomes de campo reais devem seguir o que já existe, não o que está proposto aqui.
- `crm/db.php` — padrão de conexão já estabelecido (reaproveitar `getDb()`, não criar uma conexão paralela).
- `crm/fechar-competencia.php` — provavelmente já contém a lógica de fechamento mensal do lado da receita; o fechamento de competência de despesa deveria seguir o mesmo raciocínio, se fizer sentido.
- Confirmar com o Felipe, antes de codar a integração automática, se os fornecedores operacionais realmente correspondem a técnicos cadastrados no Movidesk (ver seção abaixo) — não assumir.

Se o Claude Code perceber que a convenção real diverge do que este documento sugere (nomes de tabela, enums, formato de resposta das integrações), a convenção existente no código deve prevalecer — este documento é um ponto de partida arquitetural, não uma especificação rígida.

## Decisões já tomadas

- A maioria dos fornecedores já está cadastrada no Movidesk (prováveis consultores/técnicos que fecham chamados).
- Alguns tipos de despesa ficam **fora** do controle no sistema por ora: comissão de vendedor pontual.
- Reaproveitar a mesma taxonomia de contrato já validada do lado da receita (fixo mensal / hora aberta / banco de horas), invertendo o sentido do fluxo (pagar em vez de cobrar), em vez de criar uma modelagem nova do zero.

## Categorização das despesas

| Categoria | Exemplos | Tratamento |
|---|---|---|
| **Fornecedor operacional** | Consultores/técnicos que fecham chamados no Movidesk | Consumo automático, por técnico — ver seção abaixo |
| **Despesa fixa/recorrente** | Contabilidade, impostos, assinatura do Movidesk | Valor fixo + vencimento, sem lógica de consumo (mesmo padrão de "mensalidade fixa" já usado do lado da receita) |
| **Fora do sistema** | Comissão de vendedor pontual | Não controlado — decisão consciente, evitar burocracia sem ganho real |

## Ponto de arquitetura central: de onde vêm as horas do fornecedor

**Antes de desenhar um novo mecanismo de apontamento de horas, verificar**: os fornecedores operacionais são os mesmos técnicos que fecham chamados no Movidesk?

Se sim — o que é o cenário mais provável, dado que a Newsiga tem consultores contratados que atendem chamados — **não é necessário criar um sistema de time tracking novo**. O Movidesk já tem o campo de responsável/técnico por chamado, a mesma fonte hoje usada para calcular consumo *por cliente* (receita) pode ser reagrupada *por técnico* (custo). Uma extração, dois relatórios.

Se não (fornecedor externo que não usa Movidesk) — considerar o agente de projetos já existente no Notion (bases Projetos/Tarefas/Desenvolvedores/Alocações, hoje em `gestao.newsiga.com.br`, atualmente sem uso ativo) antes de criar apontamento manual do zero — pode já ter o dado necessário, só falta integrar.

**Ação para o Claude Code**: antes de implementar, confirmar com Felipe se essa premissa (fornecedor = técnico Movidesk) é válida para todos os fornecedores operacionais, ou só para parte deles.

## Modelagem de dados proposta

```sql
CREATE TABLE fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    tipo ENUM('operacional', 'fixo') NOT NULL,
    movidesk_technician_id VARCHAR(100) NULL, -- preenchido quando tipo = 'operacional' e o fornecedor tem correspondência no Movidesk
    forma_pagamento VARCHAR(100) NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Espelha a tabela de contratos de cliente já existente (mesmos 3 tipos), invertendo o fluxo
CREATE TABLE contratos_fornecedor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fornecedor_id INT NOT NULL,
    tipo_contrato ENUM('fixo_mensal', 'hora_aberta', 'banco_horas') NOT NULL,
    valor_fixo DECIMAL(10,2) NULL,          -- usado quando tipo_contrato = 'fixo_mensal'
    horas_previstas DECIMAL(6,2) NULL,      -- usado quando tipo_contrato = 'banco_horas'
    valor_hora DECIMAL(10,2) NULL,          -- usado quando tipo_contrato = 'hora_aberta' ou 'banco_horas'
    dia_vencimento TINYINT NOT NULL,        -- 1-28, mesmo padrão do renewal_day usado no contrato de cliente
    status ENUM('ativo', 'encerrado') NOT NULL DEFAULT 'ativo',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
);

-- Uma linha por competência (mês) por contrato de fornecedor
CREATE TABLE despesas_competencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contrato_fornecedor_id INT NOT NULL,
    competencia DATE NOT NULL,              -- primeiro dia do mês de referência
    horas_consumidas DECIMAL(6,2) NULL,     -- preenchido via Movidesk quando aplicável
    valor_calculado DECIMAL(10,2) NOT NULL,
    vencimento DATE NOT NULL,
    status_pagamento ENUM('a_pagar', 'pago', 'atrasado') NOT NULL DEFAULT 'a_pagar',
    origem ENUM('movidesk_automatico', 'manual') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_fornecedor_id) REFERENCES contratos_fornecedor(id)
);
```

> **Nota para o Claude Code**: antes de criar essas tabelas, inspecionar a estrutura real da tabela de contratos de cliente já existente (usada em `crm-newsiga-cadastro-contrato.php`) para alinhar nomes de campo e convenções (ex.: se lá se chama `renewal_day` em vez de `dia_vencimento`, manter o mesmo idioma/convenção usada no restante do banco, em vez da nomenclatura sugerida aqui).

## Cálculo automático via Movidesk (fornecedor operacional)

Espelha a lógica que já existe para calcular consumo por cliente, só que agrupando por técnico responsável em vez de por empresa:

1. Buscar chamados fechados no mês na API do Movidesk (mesmo endpoint já usado pra receita).
2. Agrupar por `technician_id` (ou campo equivalente retornado pela API) em vez de por cliente.
3. Somar horas apontadas por fornecedor/técnico no período.
4. Aplicar a lógica do `tipo_contrato` (fixo, hora aberta, banco de horas) do mesmo jeito que já é feito para o lado da receita, calculando o `valor_calculado` da competência.
5. Gravar em `despesas_competencia` com `origem = 'movidesk_automatico'`.

## Fluxo de caixa consolidado

Com receita (ASAAS/Movidesk, já existente) e despesa (Movidesk por técnico + fixos manuais) na mesma base, o relatório final é:

```
Fluxo de caixa projetado (mês) = Σ parcelas a receber (contratos de cliente)
                                − Σ despesas_competencia.valor_calculado (contratos de fornecedor)
                                − Σ despesas fixas avulsas (impostos, contabilidade)
```

## Alertas sugeridos (mesma lógica já desejada do lado da receita)

- Fornecedor ultrapassou o banco de horas previsto no contrato.
- Despesa fixa/parcela de fornecedor vence em N dias e ainda está `a_pagar`.
- Margem por cliente/projeto abaixo de um limiar (cruzando receita do contrato de cliente com custo do fornecedor alocado àquele projeto, quando aplicável).

## Fora de escopo por decisão consciente

- Comissão de vendedor pontual — não entra no sistema.
- Reconciliação automática receita × despesa por cliente/projeto pode ficar para uma segunda fase, depois que o básico (cadastro de fornecedor + cálculo de competência) estiver validado.
