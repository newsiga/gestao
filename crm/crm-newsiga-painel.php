<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Painel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Instrument+Serif:ital@1&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --cream:#f3f2ec;
    --forest:#0d2b22;
    --forest-soft:#123028;
    --lime:#b6ef2a;
    --muted:#6b7770;
    --card:#e7e6e0;
    --border:#d9d8d1;
    --white:#ffffff;
    --red:#c0503e;
    --font-display:'Manrope', system-ui, sans-serif;
    --font-italic:'Instrument Serif', serif;
    --font-ui:'Inter', system-ui, sans-serif;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  body{background:var(--cream); color:var(--forest); font-family:var(--font-ui); line-height:1.5;}
  .wrap{max-width:1160px; margin:0 auto; padding:0 32px;}

  nav{border-bottom:1px solid var(--border); padding:22px 0;}
  nav .wrap{display:flex; align-items:center; justify-content:space-between;}
  .logo{font-family:var(--font-display); font-weight:800; font-size:19px; color:var(--forest); text-decoration:none;}
  .logo span{color:var(--muted); font-weight:600; font-size:13px; margin-left:8px;}
  .nav-links{display:flex; gap:26px; font-size:13.5px; color:var(--muted); font-weight:600;}
  .nav-links a{color:var(--muted); text-decoration:none;}
  .nav-links a.active{color:var(--forest);}
  .btn-nav{font-family:var(--font-ui); font-size:13px; font-weight:700; background:var(--forest); color:var(--white); padding:10px 18px; border-radius:8px; text-decoration:none;}

  .page-head{padding:44px 0 8px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px;}
  .eyebrow{font-family:var(--font-ui); font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:14px;}
  h1{font-family:var(--font-display); font-weight:800; font-size:32px; letter-spacing:-.01em;}
  h1 em{font-family:var(--font-italic); font-style:italic; font-weight:400;}
  .draft-tag{font-family:var(--font-ui); font-size:12px; font-weight:600; color:var(--muted); border:1px solid var(--border); border-radius:20px; padding:6px 14px;}

  .period-bar{display:flex; align-items:center; gap:12px; margin-top:24px;}
  .period-label{font-size:13px; color:var(--muted); font-weight:600;}
  .period-select{
    font-family:var(--font-display); font-weight:700; font-size:14.5px; color:var(--forest);
    background:var(--white); border:1px solid var(--border); border-radius:8px; padding:10px 16px; cursor:pointer;
  }

  .kpis{display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin:36px 0;}
  .kpi{background:var(--white); border:1px solid var(--border); border-radius:12px; padding:22px;}
  .kpi .label{font-family:var(--font-ui); font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-bottom:12px;}
  .kpi .value{font-family:var(--font-display); font-size:26px; font-weight:800;}
  .kpi.warn .value{color:#a06a1f;}
  .kpi.bad .value{color:var(--red);}
  .kpi .delta{font-size:12.5px; color:var(--muted); margin-top:6px;}

  .grid-main{display:grid; grid-template-columns:1.5fr 1fr; gap:20px; margin-bottom:20px; align-items:stretch;}
  .grid-main > .panel{display:flex; flex-direction:column; min-height:0;}
  .grid-main > .panel > .panel-head{flex-shrink:0;}
  .side-stack{display:flex; flex-direction:column; gap:20px;}
  .side-stack .panel{flex:1; display:flex; flex-direction:column; min-height:0;}
  .side-stack .panel-head{flex-shrink:0;}
  #radar-container{flex:1; overflow-y:auto; min-height:0;}
  #alertas-container, #pipeline-container{flex:1; overflow-y:auto; min-height:0;}
  .panel{background:var(--white); border:1px solid var(--border); border-radius:12px; overflow:hidden;}
  .panel-head{padding:18px 22px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;}
  .panel-head h3{font-family:var(--font-display); font-size:16px; font-weight:700;}
  .panel-head .tag{font-family:var(--font-ui); font-size:12px; color:var(--muted); font-weight:600;}

  .radar-row{display:grid; grid-template-columns:90px 1fr auto auto; gap:14px; align-items:center; padding:16px 22px; border-bottom:1px solid var(--border); font-size:14px;}
  .radar-row:last-child{border-bottom:none;}
  .radar-date{font-family:var(--font-ui); font-size:13px; color:var(--muted); font-weight:600;}
  .radar-client b{display:block; font-family:var(--font-display); font-weight:700; font-size:14.5px;}
  .radar-client span{color:var(--muted); font-size:12.5px;}
  .radar-value{font-family:var(--font-display); font-weight:700; text-align:right;}
  .status-pill{font-family:var(--font-ui); font-size:11px; font-weight:700; padding:5px 12px; border-radius:20px; text-align:center; white-space:nowrap;}
  .status-pill.gerar{background:#f3e6c9; color:#8a6414;}
  .status-pill.gerado{background:#dbe9d8; color:#2f5c3f;}
  .status-pill.pago{background:#c8e6c0; color:#1f4d2c;}
  .status-pill.atraso{background:#f1d9d4; color:var(--red);}
  .status-pill.previsto{background:#e2e0d8; color:var(--muted);}

  .alert{display:flex; gap:12px; padding:16px 22px; border-bottom:1px solid var(--border); font-size:13.5px;}
  a.alert:hover{background:#faf9f6;}
  .alert:last-child{border-bottom:none;}
  .alert .dot{width:7px; height:7px; border-radius:50%; margin-top:7px; flex-shrink:0;}
  .alert.bad .dot{background:var(--red);}
  .alert.warn .dot{background:#c68a2e;}
  .alert p{color:var(--muted);}
  .alert strong{color:var(--forest); display:block; margin-bottom:2px; font-family:var(--font-display); font-weight:700; font-size:14px;}

  .table-wrap{overflow-x:auto;}
  table{width:100%; border-collapse:collapse; font-size:13.5px;}
  th{text-align:left; font-family:var(--font-ui); font-size:11.5px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; font-weight:700; padding:14px 22px; border-bottom:1px solid var(--border);}
  td{padding:16px 22px; border-bottom:1px solid var(--border);}
  tr:last-child td{border-bottom:none;}
  td.name{font-family:var(--font-display); font-weight:700;}
  .tipo-badge{font-family:var(--font-ui); font-size:11px; font-weight:600; padding:4px 10px; border-radius:6px; background:var(--card); color:var(--forest);}
  .consumo-track{width:80px; height:6px; background:var(--card); border-radius:3px; display:inline-block; overflow:hidden; margin-right:8px; vertical-align:middle;}
  .consumo-fill{height:100%; background:var(--forest);}

  tr.group-header td{background:var(--card); font-family:var(--font-display); font-weight:700; font-size:13.5px; padding:12px 22px; border-bottom:1px solid var(--border); cursor:pointer;}
  tr.group-header:hover td{background:#ddddd4;}
  tr.group-header .group-count{color:var(--muted); font-weight:600; font-size:12px; margin-left:8px;}
  tr.group-header .toggle-arrow{display:inline-block; width:14px; color:var(--muted); font-size:11px;}

  .chart-panel{padding:6px 22px 22px;}
  .bars{display:flex; align-items:flex-end; gap:18px; height:150px; margin-top:8px;}
  .bar-col{flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; height:100%;}
  .bar-value{font-family:var(--font-display); font-size:11px; font-weight:700; color:var(--muted); margin-bottom:6px;}
  .bar{width:100%; max-width:46px; background:var(--card); border-radius:6px 6px 0 0;}
  .bar.current{background:var(--forest);}
  .bar-label{font-size:11.5px; color:var(--muted); margin-top:10px; font-weight:600;}

  footer.note{margin:24px 0 60px; padding:20px 24px; background:var(--card); border-radius:12px; font-size:13.5px; color:var(--muted);}
  footer.note b{color:var(--forest);}

  @media (max-width:900px){
    .kpis{grid-template-columns:1fr 1fr;}
    .grid-main{grid-template-columns:1fr;}
    .nav-links{display:none;}
    .radar-row{grid-template-columns:70px 1fr; row-gap:6px;}
    .radar-value, .status-pill{grid-column:2;}
  }
</style>
</head>
<body>

<nav>
  <div class="wrap">
    <a class="logo" href="crm-newsiga-painel.php">crm<span>.newsiga</span></a>
    <div class="nav-links">
      <a href="crm-newsiga-painel.php" class="active">Painel</a>
      <a href="crm-newsiga-clientes.php">Clientes</a>
      <a href="crm-newsiga-contratos.php">Contratos</a>
      <a href="crm-newsiga-fornecedores.php">Fornecedores</a>
      <a href="crm-newsiga-despesas.php">Despesas</a>
      <a href="crm-newsiga-prospects.php">Prospects</a>
    </div>
    <a class="btn-nav" href="crm-newsiga-cadastro-contrato.php">+ Novo contrato</a>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div>
      <div class="eyebrow">painel financeiro</div>
      <h1>Tudo que precisa de <em>atenção</em>, num só lugar.</h1>
    </div>
  </div>

  <div class="period-bar">
    <div class="period-label">Analisando o fechamento de</div>
    <select class="period-select" id="period-select"></select>
  </div>

  <div class="kpis">
    <div class="kpi">
      <div class="label">Previsão do mês</div>
      <div class="value" id="kpi-previsao-valor">—</div>
      <div class="delta" id="kpi-previsao-delta"></div>
    </div>
    <div class="kpi warn">
      <div class="label">A gerar essa semana</div>
      <div class="value" id="kpi-gerar-valor">—</div>
      <div class="delta" id="kpi-gerar-delta"></div>
    </div>
    <div class="kpi bad">
      <div class="label">Em atraso</div>
      <div class="value" id="kpi-atraso-valor">—</div>
      <div class="delta" id="kpi-atraso-delta"></div>
    </div>
    <div class="kpi">
      <div class="label">Projetos sem fatura</div>
      <div class="value" id="kpi-projetos-valor">—</div>
      <div class="delta" id="kpi-projetos-delta"></div>
    </div>
  </div>

  <div class="grid-main">
    <div class="panel">
      <div class="panel-head">
        <h3>Radar de cobranças</h3>
        <span class="tag">ordenado por vencimento</span>
      </div>
      <div id="radar-container">
        <div class="radar-row" style="grid-template-columns:1fr;">
          <div style="color:var(--muted); font-style:italic;">Carregando cobranças...</div>
        </div>
      </div>
    </div>

    <div class="side-stack">
      <div class="panel">
        <div class="panel-head">
          <h3>Alertas</h3>
          <span class="tag" id="alertas-tag">—</span>
        </div>
        <div id="alertas-container">
          <div class="alert"><p style="color:var(--muted); font-style:italic;">Carregando alertas...</p></div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <h3>Pipeline comercial</h3>
          <span class="tag" id="pipeline-tag">—</span>
        </div>
        <div id="pipeline-container">
          <div class="radar-row" style="grid-template-columns:1fr;">
            <div style="color:var(--muted); font-style:italic;">Carregando pipeline...</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h3>Faturamento por mês</h3>
      <span class="tag">últimos 6 meses</span>
    </div>
    <div class="chart-panel">
      <div class="bars" id="bars-container">
        <div style="color:var(--muted); font-style:italic;">Carregando faturamento...</div>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h3>Contratos ativos</h3>
      <span class="tag" id="contratos-ativos-tag">—</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Descrição</th><th>Tipo</th><th>Status</th></tr>
        </thead>
        <tbody id="contratos-tbody">
          <tr><td colspan="3" style="color:var(--muted); font-style:italic;">Carregando contratos...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <footer class="note">
    <b>Radar de cobranças, faturamento por mês, alertas, pipeline comercial, contratos ativos e os 4 cards de resumo já leem do banco de dados real — incluindo projetos parcelados (tabela `parcelas`).</b>
    Radar, Previsão e Faturamento por mês agora usam o mês do VENCIMENTO (quando a cobrança cai de verdade no ASAAS), não a competência (mês do serviço) — é isso que bate com o extrato real.
    Os alertas hoje só cobrem "próximo contato" de prospects.
    "Previsão do mês" mostra só o que já é certo: valor fixo (sempre) + parcelas de projeto do mês + contratos por hora que já tiverem fatura real (pós-fechamento). Contratos por hora sem fatura ainda não entram — sem estimativa, só valor calculado de verdade.
  </footer>
</div>

<script>
  const tipoLabels = {
    mensalidade_fixa: 'valor fixo',
    hora_aberta: 'hora aberta',
    banco_horas_minimo: 'banco c/ mínimo',
    banco_horas_consumo: 'banco s/ mínimo',
    projeto_parcelado: 'projeto parcelado',
  };
  const statusLabels = { rascunho: 'rascunho', aprovado: 'aprovado', ativo: 'ativo', concluido: 'concluído', encerrado: 'encerrado' };
  const statusClass = { ativo: 'gerado', aprovado: 'gerar', rascunho: 'gerar', concluido: 'gerado', encerrado: 'atraso' };
  const fmtMoedaPt = (v) => 'R$ ' + Number(v).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const hojeISO = () => new Date().toISOString().slice(0, 10);

  // ---- Seletor de competência: gerado dinamicamente, sempre com o mês
  // atual pré-selecionado — nunca mais precisa editar o HTML todo mês.
  const nomesMesCompleto = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
  function popularSeletorCompetencia() {
    const select = document.getElementById('period-select');
    const hoje = new Date();
    const opcoes = [];
    // 3 meses passados + mês atual + 1 mês futuro (pra já poder ver a
    // previsão/estimativa do próximo antes dele fechar)
    for (let i = -3; i <= 1; i++) {
      const d = new Date(hoje.getFullYear(), hoje.getMonth() + i, 1);
      const valor = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
      const label = `${nomesMesCompleto[d.getMonth()].charAt(0).toUpperCase() + nomesMesCompleto[d.getMonth()].slice(1)} · ${d.getFullYear()}`;
      opcoes.push({ valor, label });
    }
    const competenciaAtual = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}`;
    select.innerHTML = opcoes.map(o => `<option value="${o.valor}"${o.valor === competenciaAtual ? ' selected' : ''}>${o.label}</option>`).join('');
    return competenciaAtual;
  }
  const competenciaInicial = popularSeletorCompetencia();

  let todosContratosGlobal = [];
  let todasFaturasGlobal = [];
  let todasParcelasPendentesGlobal = [];

  function renderContratosAtivos() {
    const tbody = document.getElementById('contratos-tbody');
    const tag = document.getElementById('contratos-ativos-tag');
    const ativos = todosContratosGlobal.filter(c => c.status === 'ativo');

    tag.textContent = `${ativos.length} contrato${ativos.length === 1 ? '' : 's'}`;

    if (ativos.length === 0) {
      tbody.innerHTML = '<tr><td colspan="3" style="color:var(--muted); font-style:italic; padding:16px 22px;">Nenhum contrato ativo no momento.</td></tr>';
      return;
    }

    const grupos = [];
    const indicePorCliente = {};
    ativos.forEach(c => {
      if (!(c.cliente_id in indicePorCliente)) {
        indicePorCliente[c.cliente_id] = grupos.length;
        grupos.push({ cliente_id: c.cliente_id, cliente_nome: c.cliente_nome, contratos: [] });
      }
      grupos[indicePorCliente[c.cliente_id]].contratos.push(c);
    });
    grupos.sort((a, b) => a.cliente_nome.localeCompare(b.cliente_nome, 'pt-BR'));

    tbody.innerHTML = grupos.map(grupo => {
      const linhas = grupo.contratos.map(c => `
        <tr class="group-body" data-group="${grupo.cliente_id}" style="display:none;">
          <td style="color:var(--muted);">${c.descricao || '—'}</td>
          <td><span class="tipo-badge">${tipoLabels[c.tipo] || c.tipo}</span></td>
          <td><span class="status-pill ${statusClass[c.status] || 'gerar'}">${statusLabels[c.status] || c.status}</span></td>
        </tr>`).join('');
      const qtd = grupo.contratos.length;
      return `
        <tr class="group-header" data-group="${grupo.cliente_id}">
          <td colspan="3"><span class="toggle-arrow">▸</span>${grupo.cliente_nome}<span class="group-count">${qtd} contrato${qtd === 1 ? '' : 's'}</span></td>
        </tr>
        ${linhas}`;
    }).join('');

    document.querySelectorAll('#contratos-tbody .group-header').forEach(row => {
      row.addEventListener('click', () => {
        const grupoId = row.dataset.group;
        const abrindo = row.classList.toggle('open');
        row.querySelector('.toggle-arrow').textContent = abrindo ? '▾' : '▸';
        document.querySelectorAll(`#contratos-tbody .group-body[data-group="${grupoId}"]`).forEach(linha => {
          linha.style.display = abrindo ? 'table-row' : 'none';
        });
      });
    });
  }

  function renderKpis(competencia) {
    const hoje = hojeISO();
    const em7dias = new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10);

    // ---- Previsão do mês — mostra o que já é CERTO pra essa competência:
    // valor fixo (sempre certo, mesmo antes do fechamento) + parcelas de
    // projeto com vencimento no mês + contratos por hora, mas SÓ os que já
    // tiverem fatura real gerada (antes do fechamento, eles simplesmente
    // não entram — nada de estimativa/adivinhação).
    const ativos = todosContratosGlobal.filter(c => c.status === 'ativo');
    let total = 0, qtdReal = 0, qtdFixo = 0;
    ativos.forEach(c => {
      if (c.tipo === 'projeto_parcelado') return; // parcelas contadas à parte, abaixo

      const faturaDessaCompetencia = todasFaturasGlobal.find(f => f.contrato_id === c.id && f.vencimento.slice(0, 7) === competencia);
      if (faturaDessaCompetencia) {
        // Já fechou — usa o valor real calculado, seja qual for o tipo.
        total += Number(faturaDessaCompetencia.valor);
        qtdReal++;
        return;
      }

      if (c.tipo === 'mensalidade_fixa') {
        // Valor fixo é sempre certo, mesmo antes de fechar o mês.
        total += Number(c.valor || 0);
        qtdFixo++;
      }
      // banco_horas_minimo / hora_aberta / banco_horas_consumo sem fatura
      // ainda: não entram na previsão — só saberemos o valor depois do
      // fechamento (consumo do mês ainda não está definido).
    });

    // Projetos parcelados: soma a(s) parcela(s) com vencimento no mês
    // selecionado — valor real, já sabido, nunca precisa de estimativa.
    let qtdParcelas = 0;
    todasParcelasPendentesGlobal
      .filter(p => p.vencimento.slice(0, 7) === competencia)
      .forEach(p => {
        total += Number(p.valor);
        qtdParcelas++;
      });

    const previsaoValor = fmtMoedaPt(total);
    const partes = [];
    if (qtdFixo > 0) partes.push(`${qtdFixo} valor fixo`);
    if (qtdParcelas > 0) partes.push(`${qtdParcelas} parcela${qtdParcelas === 1 ? '' : 's'} de projeto`);
    if (qtdReal > 0) partes.push(`${qtdReal} já fechado${qtdReal === 1 ? '' : 's'} (valor/hora calculado)`);
    const previsaoDelta = partes.length > 0 ? partes.join(' + ') : 'nada certo ainda pra essa competência';
    document.getElementById('kpi-previsao-valor').textContent = previsaoValor;
    document.getElementById('kpi-previsao-delta').textContent = previsaoDelta;

    // ---- A gerar essa semana ----
    // Faturas ainda não geradas + parcelas de projeto ainda não geradas,
    // ambas com vencimento nos próximos 7 dias (antes só olhava faturas).
    const faturasAGerarSemana = todasFaturasGlobal.filter(f => f.status === 'a_gerar' && f.vencimento >= hoje && f.vencimento <= em7dias);
    const parcelasAGerarSemana = todasParcelasPendentesGlobal.filter(p => p.status === 'a_gerar' && p.vencimento >= hoje && p.vencimento <= em7dias);
    const valorGerarSemana = faturasAGerarSemana.reduce((s, f) => s + Number(f.valor), 0) + parcelasAGerarSemana.reduce((s, p) => s + Number(p.valor), 0);
    const qtdGerarSemana = faturasAGerarSemana.length + parcelasAGerarSemana.length;
    document.getElementById('kpi-gerar-valor').textContent = fmtMoedaPt(valorGerarSemana);
    document.getElementById('kpi-gerar-delta').textContent = `${qtdGerarSemana} cobrança${qtdGerarSemana === 1 ? '' : 's'} pendente${qtdGerarSemana === 1 ? '' : 's'}`;

    // ---- Em atraso (faturas + parcelas de projeto) ----
    // Cobre dois casos: nunca foi gerada e o vencimento já passou (falha
    // do fechamento em gerar a tempo), OU o ASAAS já confirmou o atraso
    // de verdade (status 'atrasado', vindo do webhook PAYMENT_OVERDUE —
    // só existe pra parcela, não pra fatura recorrente).
    const faturasAtrasadas = todasFaturasGlobal.filter(f => f.status === 'a_gerar' && f.vencimento < hoje);
    const parcelasEmAtraso = todasParcelasPendentesGlobal.filter(p => (p.status === 'a_gerar' && p.vencimento < hoje) || p.status === 'atrasado');
    const valorAtraso = faturasAtrasadas.reduce((s, f) => s + Number(f.valor), 0) + parcelasEmAtraso.reduce((s, p) => s + Number(p.valor), 0);
    const qtdAtraso = faturasAtrasadas.length + parcelasEmAtraso.length;
    document.getElementById('kpi-atraso-valor').textContent = fmtMoedaPt(valorAtraso);
    document.getElementById('kpi-atraso-delta').textContent = `${qtdAtraso} cobrança${qtdAtraso === 1 ? '' : 's'} não gerada${qtdAtraso === 1 ? '' : 's'} a tempo`;

    // ---- Projetos sem fatura ----
    const parcelasAtrasadas = todasParcelasPendentesGlobal.filter(p => (p.status === 'a_gerar' && p.vencimento < hoje) || p.status === 'atrasado');
    const contratosAfetados = new Set(parcelasAtrasadas.map(p => p.contrato_id));
    document.getElementById('kpi-projetos-valor').textContent = contratosAfetados.size;
    document.getElementById('kpi-projetos-delta').textContent = contratosAfetados.size > 0
      ? `${parcelasAtrasadas.length} parcela${parcelasAtrasadas.length === 1 ? '' : 's'} atrasada${parcelasAtrasadas.length === 1 ? '' : 's'}`
      : 'nenhum projeto pendente';
  }

  document.getElementById('period-select').addEventListener('change', (e) => {
    renderKpis(e.target.value);
    renderRadar(e.target.value);
  });

  Promise.all([
    fetch('listar-contratos.php').then(r => r.json()).catch(() => ({ sucesso: false })),
    fetch('listar-faturas.php').then(r => r.json()).catch(() => ({ sucesso: false })),
    fetch('listar-parcelas-pendentes.php').then(r => r.json()).catch(() => ({ sucesso: false })),
  ]).then(([contratosData, faturasData, parcelasData]) => {
    todosContratosGlobal = (contratosData.sucesso && contratosData.contratos) ? contratosData.contratos : [];
    todasFaturasGlobal = (faturasData.sucesso && faturasData.faturas) ? faturasData.faturas : [];
    todasParcelasPendentesGlobal = (parcelasData.sucesso && parcelasData.parcelas) ? parcelasData.parcelas : [];

    renderContratosAtivos();
    renderKpis(document.getElementById('period-select').value);
    renderRadar(document.getElementById('period-select').value);
  }).catch(() => {
    document.getElementById('contratos-tbody').innerHTML =
      '<tr><td colspan="3" style="color:var(--red);">Falha ao carregar contratos do servidor.</td></tr>';
  });

  // ---- Radar de cobranças ----
  const tipoLabelsFatura = {
    mensalidade_fixa: 'mensalidade fixa',
    hora_aberta: 'hora aberta',
    banco_horas_minimo: 'banco de horas — mínimo garantido',
    banco_horas_consumo: 'banco de horas — sem mínimo',
  };
  const fmtMoeda = (v) => 'R$ ' + Number(v).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const fmtDataCurta = (iso) => {
    const [ano, mes, dia] = iso.split('-');
    return `${dia}/${mes}`;
  };

  function renderRadar(competencia) {
    const container = document.getElementById('radar-container');
    const hoje = hojeISO();

    // Mostra as faturas cujo VENCIMENTO cai no mês selecionado (não a
    // competência — competência é o mês do serviço, vencimento é quando
    // cai no ASAAS de verdade, é isso que precisa bater com o extrato).
    const faturasDaCompetencia = todasFaturasGlobal
      .filter(f => f.vencimento.slice(0, 7) === competencia)
      .map(f => ({
        vencimento: f.vencimento, cliente_nome: f.cliente_nome, valor: f.valor, status: f.status,
        subtitulo: (tipoLabelsFatura[f.contrato_tipo] || f.contrato_tipo) + (f.contrato_descricao ? ' · ' + f.contrato_descricao : ''),
      }));

    const parcelasDoMes = todasParcelasPendentesGlobal
      .filter(p => p.vencimento.slice(0, 7) === competencia)
      .map(p => ({
        vencimento: p.vencimento, cliente_nome: p.cliente_nome, valor: p.valor, status: p.status,
        subtitulo: `projeto parcelado · ${p.contrato_descricao || ''} (parcela ${p.numero})`,
      }));

    // Valor fixo sem fatura ainda pra esse mês — mesma regra da Previsão
    // do mês: já é certo, mesmo antes do fechamento gerar de verdade.
    // Marca como 'previsto', separado de 'gerado'/'a gerar' reais.
    const ultimoDiaDoMes = new Date(Number(competencia.slice(0, 4)), Number(competencia.slice(5, 7)), 0).getDate();
    const fixosPrevistos = todosContratosGlobal
      .filter(c => c.status === 'ativo' && c.tipo === 'mensalidade_fixa')
      .filter(c => !todasFaturasGlobal.some(f => f.contrato_id === c.id && f.vencimento.slice(0, 7) === competencia))
      .map(c => {
        const dia = String(Math.min(Number(c.dia_vencimento) || 1, ultimoDiaDoMes)).padStart(2, '0');
        return {
          vencimento: `${competencia}-${dia}`, cliente_nome: c.cliente_nome, valor: c.valor, status: 'previsto',
          subtitulo: 'mensalidade fixa · previsto (fatura ainda não gerada)',
        };
      });

    const daCompetencia = [...faturasDaCompetencia, ...parcelasDoMes, ...fixosPrevistos]
      .sort((a, b) => a.vencimento.localeCompare(b.vencimento));

    if (daCompetencia.length === 0) {
      container.innerHTML = '<div class="radar-row" style="grid-template-columns:1fr;"><div style="color:var(--muted);">Nenhuma fatura gerada ainda para esta competência.</div></div>';
      return;
    }

    container.innerHTML = daCompetencia.map(f => {
      // 'pago' passou a entrar nessa lista (antes ficava de fora na
      // origem) — precisa de estado próprio, senão cai na regra de
      // "vencida" e mostra atrasado pra algo que já foi recebido.
      const vencida = f.vencimento < hoje && f.status !== 'gerado' && f.status !== 'previsto' && f.status !== 'pago';
      const pillClasse = f.status === 'previsto' ? 'previsto' : (f.status === 'pago' ? 'pago' : (vencida ? 'atraso' : (f.status === 'gerado' ? 'gerado' : 'gerar')));
      const pillTexto = f.status === 'previsto' ? 'previsto' : (f.status === 'pago' ? 'pago' : (vencida ? 'atrasado' : (f.status === 'gerado' ? 'gerado' : 'a gerar')));
      return `
        <div class="radar-row">
          <div class="radar-date">${fmtDataCurta(f.vencimento)}</div>
          <div class="radar-client"><b>${f.cliente_nome}</b><span>${f.subtitulo}</span></div>
          <div class="radar-value">${fmtMoeda(f.valor)}</div>
          <span class="status-pill ${pillClasse}">${pillTexto}</span>
        </div>`;
    }).join('');
  }

  // ---- Faturamento por mês ----
  const nomesMes = { '01':'Jan','02':'Fev','03':'Mar','04':'Abr','05':'Mai','06':'Jun','07':'Jul','08':'Ago','09':'Set','10':'Out','11':'Nov','12':'Dez' };

  fetch('faturamento-mensal.php')
    .then(r => r.json())
    .then(data => {
      const container = document.getElementById('bars-container');
      if (!data.sucesso || !data.faturamento || data.faturamento.length === 0) {
        container.innerHTML = '<div style="color:var(--muted); font-style:italic;">Nenhuma fatura gerada ainda.</div>';
        return;
      }

      const maior = Math.max(...data.faturamento.map(f => Number(f.total)));
      const ultimoIndice = data.faturamento.length - 1;

      container.innerHTML = data.faturamento.map((f, i) => {
        const [, mes] = f.competencia.split('-');
        const valorExato = fmtMoedaPt(f.total);
        const valorK = (Number(f.total) / 1000).toFixed(Number(f.total) >= 10000 ? 0 : 1).replace('.', ',') + 'k';
        const altura = maior > 0 ? Math.max(6, (Number(f.total) / maior) * 100) : 6;
        const classeAtual = i === ultimoIndice ? ' current' : '';
        return `
          <div class="bar-col" title="${valorExato}">
            <div class="bar-value">${valorK}</div>
            <div class="bar${classeAtual}" style="height:${altura}%;"></div>
            <div class="bar-label">${nomesMes[mes] || mes}</div>
          </div>`;
      }).join('');
    })
    .catch(() => {
      document.getElementById('bars-container').innerHTML =
        '<div style="color:var(--red);">Falha ao carregar faturamento.</div>';
    });

  // ---- Alertas — baseado na data de próximo contato dos prospects ----
  fetch('listar-prospects.php')
    .then(r => r.json())
    .then(data => {
      const container = document.getElementById('alertas-container');
      const tag = document.getElementById('alertas-tag');
      const hoje = new Date().toISOString().slice(0, 10);
      const em7dias = new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10);

      const comAlerta = (data.prospects || [])
        .filter(p => p.proximo_contato && p.estagio !== 'ganho' && p.estagio !== 'perdido' && p.proximo_contato <= em7dias)
        .sort((a, b) => a.proximo_contato.localeCompare(b.proximo_contato));

      tag.textContent = comAlerta.length;

      if (!data.sucesso || comAlerta.length === 0) {
        container.innerHTML = '<div class="alert"><p style="color:var(--muted);">Nenhum contato pendente nos próximos 7 dias.</p></div>';
        return;
      }

      container.innerHTML = comAlerta.map(p => {
        const vencido = p.proximo_contato < hoje;
        const [ano, mes, dia] = p.proximo_contato.split('-');
        const classe = vencido ? 'bad' : 'warn';
        const diasDiff = Math.round((new Date(p.proximo_contato) - new Date(hoje)) / 86400000);
        let quando;
        if (vencido) quando = `${Math.abs(diasDiff)} dia${Math.abs(diasDiff) === 1 ? '' : 's'} em atraso`;
        else if (diasDiff === 0) quando = 'hoje';
        else quando = `em ${diasDiff} dia${diasDiff === 1 ? '' : 's'}`;
        return `
          <a class="alert ${classe}" href="crm-newsiga-cadastro-prospect.php?id=${p.id}" style="text-decoration:none; color:inherit; cursor:pointer;">
            <div class="dot"></div>
            <p><strong>${p.nome} — retomar contato ${quando}</strong>Previsto pra ${dia}/${mes}${p.descricao ? ' · ' + p.descricao : ''}</p>
          </a>`;
      }).join('');
    })
    .catch(() => {
      document.getElementById('alertas-container').innerHTML =
        '<div class="alert"><p style="color:var(--red);">Falha ao carregar alertas.</p></div>';
    });

  // ---- Pipeline comercial ----
  const estagioLabelsPipeline = {
    contato_inicial: 'contato inicial',
    em_negociacao: 'em negociação',
    proposta_enviada: 'proposta enviada',
    ganho: 'ganho',
    perdido: 'perdido',
  };
  const estagioClassePipeline = {
    contato_inicial: 'gerado', // reaproveita o cinza neutro do status-pill via override abaixo
    em_negociacao: 'gerar',
    proposta_enviada: 'gerar',
    ganho: 'gerado',
    perdido: 'atraso',
  };

  fetch('listar-prospects.php')
    .then(r => r.json())
    .then(data => {
      const container = document.getElementById('pipeline-container');
      const tag = document.getElementById('pipeline-tag');
      const emAndamento = (data.prospects || []).filter(p => p.estagio !== 'ganho' && p.estagio !== 'perdido');

      tag.textContent = `${emAndamento.length} em andamento`;

      if (!data.sucesso || emAndamento.length === 0) {
        container.innerHTML = `<div class="radar-row" style="grid-template-columns:1fr;"><div style="color:var(--muted);">Nenhum prospect em andamento. <a href="crm-newsiga-cadastro-prospect.php" style="color:var(--forest); font-weight:600;">Cadastrar um →</a></div></div>`;
        return;
      }

      container.innerHTML = emAndamento.map(p => {
        const corNeutra = p.estagio === 'contato_inicial' ? ' style="background:#e2e0d8; color:var(--muted);"' : '';
        return `
          <div class="radar-row" style="grid-template-columns:1fr auto;">
            <div class="radar-client"><b>${p.nome}</b><span>${p.descricao || estagioLabelsPipeline[p.estagio]}</span></div>
            <span class="status-pill ${estagioClassePipeline[p.estagio]}"${corNeutra}>${estagioLabelsPipeline[p.estagio]}</span>
          </div>`;
      }).join('');
    })
    .catch(() => {
      document.getElementById('pipeline-container').innerHTML =
        '<div class="radar-row" style="grid-template-columns:1fr;"><div style="color:var(--red);">Falha ao carregar pipeline.</div></div>';
    });
</script>

</body>
</html>
