<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Despesas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Instrument+Serif:ital@1&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --cream:#f3f2ec; --forest:#0d2b22; --forest-soft:#123028; --lime:#b6ef2a;
    --muted:#6b7770; --card:#e7e6e0; --border:#d9d8d1; --white:#ffffff; --red:#c0503e;
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
  .nav-links{display:flex; gap:22px; font-size:13.5px; color:var(--muted); font-weight:600;}
  .nav-links a{color:var(--muted); text-decoration:none;}
  .nav-links a.active{color:var(--forest);}
  .btn-nav{font-family:var(--font-ui); font-size:13px; font-weight:700; background:var(--forest); color:var(--white); padding:10px 18px; border-radius:8px; text-decoration:none;}
  .page-head{padding:44px 0 8px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px;}
  .eyebrow{font-family:var(--font-ui); font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:14px;}
  h1{font-family:var(--font-display); font-weight:800; font-size:32px; letter-spacing:-.01em;}
  h1 em{font-family:var(--font-italic); font-style:italic; font-weight:400;}

  .period-bar{display:flex; align-items:center; gap:12px; margin-top:24px;}
  .period-label{font-size:13px; color:var(--muted); font-weight:600;}
  .period-select{
    font-family:var(--font-display); font-weight:700; font-size:14.5px; color:var(--forest);
    background:var(--white); border:1px solid var(--border); border-radius:8px; padding:10px 16px; cursor:pointer;
  }

  .kpis{display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin:28px 0 20px;}
  .kpi{background:var(--white); border:1px solid var(--border); border-radius:12px; padding:22px;}
  .kpi .label{font-family:var(--font-ui); font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-bottom:12px;}
  .kpi .value{font-family:var(--font-display); font-size:24px; font-weight:800;}
  .kpi.warn .value{color:#a06a1f;}
  .kpi.bad .value{color:var(--red);}
  .kpi.good .value{color:#2f5c3f;}
  .kpi .delta{font-size:12.5px; color:var(--muted); margin-top:6px;}
  @media (max-width:860px){.kpis{grid-template-columns:1fr 1fr;}}

  .filters{display:flex; gap:8px; margin:20px 0; flex-wrap:wrap;}
  .filter-btn{font-family:var(--font-ui); font-size:13px; font-weight:600; padding:9px 16px; border-radius:20px; border:1px solid var(--border); background:var(--white); color:var(--muted); cursor:pointer;}
  .filter-btn.active{background:var(--forest); color:var(--white); border-color:var(--forest);}

  .panel{background:var(--white); border:1px solid var(--border); border-radius:12px; overflow:hidden; margin-bottom:60px;}
  table{width:100%; border-collapse:collapse; font-size:13.5px;}
  th{text-align:left; font-family:var(--font-ui); font-size:11.5px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; font-weight:700; padding:14px 22px; border-bottom:1px solid var(--border);}
  td{padding:16px 22px; border-bottom:1px solid var(--border); vertical-align:middle;}
  tr:last-child td{border-bottom:none;}
  td.name{font-family:var(--font-display); font-weight:700;}

  .status-select{font-family:var(--font-ui); font-size:12.5px; font-weight:600; color:var(--forest); background:var(--white); border:1px solid var(--border); border-radius:6px; padding:6px 10px; cursor:pointer;}
  .row-msg{font-size:11.5px; color:var(--muted); margin-top:4px;}
  .row-msg.error{color:var(--red);}

  .origem-pill{font-size:11px; font-weight:600; color:var(--muted); margin-left:8px;}

  tr.categoria-header td{background:var(--card); font-family:var(--font-display); font-weight:700; font-size:13.5px; padding:12px 22px; border-bottom:1px solid var(--border);}
  tr.categoria-header .categoria-total{float:right; color:var(--forest);}
  tr.categoria-body td.name{padding-left:36px; font-weight:400; font-family:var(--font-ui);}
  .empty-state{padding:60px 22px; text-align:center; color:var(--muted); font-size:14px;}

  @media (max-width:860px){
    .nav-links{display:none;}
    table{font-size:12.5px;}
    th, td{padding:12px 14px;}
  }
</style>
</head>
<body>

<nav>
  <div class="wrap">
    <a class="logo" href="crm-newsiga-painel.php">crm<span>.newsiga</span></a>
    <div class="nav-links">
      <a href="crm-newsiga-painel.php">Painel</a>
      <a href="crm-newsiga-clientes.php">Clientes</a>
      <a href="crm-newsiga-contratos.php">Contratos</a>
      <a href="crm-newsiga-fornecedores.php">Fornecedores</a>
      <a href="crm-newsiga-despesas.php" class="active">Despesas</a>
      <a href="crm-newsiga-prospects.php">Prospects</a>
    </div>
    <a class="btn-nav" href="crm-newsiga-lancar-despesa.php">+ Lançar despesa</a>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div>
      <div class="eyebrow">despesas de fornecedor</div>
      <h1>Fluxo de caixa: receita <em>menos</em> despesa.</h1>
    </div>
  </div>

  <div class="period-bar">
    <div class="period-label">Analisando a competência de</div>
    <select class="period-select" id="period-select"></select>
  </div>

  <div class="kpis">
    <div class="kpi">
      <div class="label">Receita do mês (previsão)</div>
      <div class="value" id="kpi-receita">—</div>
      <div class="delta" id="kpi-receita-delta"></div>
    </div>
    <div class="kpi">
      <div class="label">Despesa do mês</div>
      <div class="value" id="kpi-despesa">—</div>
      <div class="delta" id="kpi-despesa-delta"></div>
    </div>
    <div class="kpi good" id="kpi-fluxo-card">
      <div class="label">Fluxo de caixa projetado</div>
      <div class="value" id="kpi-fluxo">—</div>
      <div class="delta">receita − despesa, mesmo mês de vencimento</div>
    </div>
    <div class="kpi bad">
      <div class="label">Despesa em atraso</div>
      <div class="value" id="kpi-atraso">—</div>
      <div class="delta" id="kpi-atraso-delta"></div>
    </div>
  </div>

  <div class="section-head" style="display:flex; justify-content:space-between; align-items:center; margin:36px 0 16px;">
    <h2 style="font-family:var(--font-display); font-size:18px; font-weight:800;">Custo por fornecedor — mês atual</h2>
    <span style="font-size:12.5px; color:var(--muted);" id="custo-fornecedor-tag">—</span>
  </div>
  <div class="panel">
    <table>
      <thead>
        <tr><th>Fornecedor</th><th>Lançamentos</th><th>Total do mês</th></tr>
      </thead>
      <tbody id="custo-fornecedor-tbody">
        <tr><td colspan="3" style="color:var(--muted); font-style:italic; padding:24px;">Carregando...</td></tr>
      </tbody>
    </table>
  </div>

  <div class="filters" id="filters">
    <button class="filter-btn active" data-status="">Todas</button>
    <button class="filter-btn" data-status="a_pagar">A pagar</button>
    <button class="filter-btn" data-status="pago">Pago</button>
    <button class="filter-btn" data-status="atrasado">Atrasado</button>
  </div>

  <div class="panel">
    <table>
      <thead>
        <tr><th>Fornecedor</th><th>Competência</th><th>Vencimento</th><th>Valor</th><th>Status</th><th></th></tr>
      </thead>
      <tbody id="despesas-tbody">
        <tr><td colspan="6" style="color:var(--muted); font-style:italic; padding:24px;">Carregando despesas...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  const fmt = (v) => 'R$ ' + Number(v).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const hojeISO = () => new Date().toISOString().slice(0, 10);
  const mesAtual = () => hojeISO().slice(0, 7);

  // ---- Seletor de competência — mesmo padrão do painel (crm-newsiga-painel.php) ----
  const nomesMesCompleto = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
  function popularSeletorCompetencia() {
    const select = document.getElementById('period-select');
    const hoje = new Date();
    const opcoes = [];
    // 6 meses passados + mês atual + 1 mês futuro — janela maior que a
    // do painel porque despesa manual às vezes é lançada com atraso
    // (ex: reconstituir histórico), não só olhando pra frente.
    for (let i = -6; i <= 1; i++) {
      const d = new Date(hoje.getFullYear(), hoje.getMonth() + i, 1);
      const valor = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
      const label = `${nomesMesCompleto[d.getMonth()].charAt(0).toUpperCase() + nomesMesCompleto[d.getMonth()].slice(1)} · ${d.getFullYear()}`;
      opcoes.push({ valor, label });
    }
    const competenciaAtual = mesAtual();
    select.innerHTML = opcoes.map(o => `<option value="${o.valor}"${o.valor === competenciaAtual ? ' selected' : ''}>${o.label}</option>`).join('');
    return competenciaAtual;
  }
  const competenciaInicial = popularSeletorCompetencia();

  const proximosStatus = {
    a_pagar: ['a_pagar', 'pago', 'atrasado'],
    atrasado: ['atrasado', 'pago'],
    pago: ['pago', 'a_pagar'],
  };
  const statusLabels = { a_pagar: 'A pagar', pago: 'Pago', atrasado: 'Atrasado' };

  let todasDespesas = [];
  let todasFaturas = [];
  let todosContratos = [];
  let todasParcelasPendentes = [];
  let filtroAtivo = '';

  // Mesma fórmula de "Previsão do mês" do painel geral
  // (crm-newsiga-painel.php) — não é só a soma das faturas já geradas:
  // contrato de valor fixo sem fatura ainda entra pelo valor certo do
  // contrato (já é certo antes do fechamento), e parcela de projeto
  // parcelado com vencimento no mês entra também. Precisa ser a MESMA
  // conta dos dois lugares, senão os dois cards de "receita do mês"
  // mostram números diferentes pro mesmo mês (confuso).
  function calcularReceitaPrevista(mes) {
    const ativos = todosContratos.filter(c => c.status === 'ativo');
    let total = 0, qtd = 0;
    ativos.forEach(c => {
      if (c.tipo === 'projeto_parcelado') return;
      const faturaDessaCompetencia = todasFaturas.find(f => f.contrato_id === c.id && f.vencimento.slice(0, 7) === mes);
      if (faturaDessaCompetencia) {
        total += Number(faturaDessaCompetencia.valor);
        qtd++;
        return;
      }
      if (c.tipo === 'mensalidade_fixa') {
        total += Number(c.valor || 0);
        qtd++;
      }
    });
    todasParcelasPendentes
      .filter(p => p.vencimento.slice(0, 7) === mes)
      .forEach(p => { total += Number(p.valor); qtd++; });
    return { total, qtd };
  }

  function renderKpis(competencia) {
    const mes = competencia || mesAtual();
    const hoje = hojeISO();

    const despesasDoMes = todasDespesas.filter(d => d.vencimento.slice(0, 7) === mes);
    const totalDespesa = despesasDoMes.reduce((s, d) => s + Number(d.valor), 0);
    document.getElementById('kpi-despesa').textContent = fmt(totalDespesa);
    document.getElementById('kpi-despesa-delta').textContent = `${despesasDoMes.length} lançamento${despesasDoMes.length === 1 ? '' : 's'}`;

    const { total: totalReceita, qtd: qtdReceita } = calcularReceitaPrevista(mes);
    document.getElementById('kpi-receita').textContent = fmt(totalReceita);
    document.getElementById('kpi-receita-delta').textContent = `${qtdReceita} contrato${qtdReceita === 1 ? '' : 's'}/parcela${qtdReceita === 1 ? '' : 's'} — mesma previsão do painel`;

    const fluxo = totalReceita - totalDespesa;
    document.getElementById('kpi-fluxo').textContent = fmt(fluxo);
    document.getElementById('kpi-fluxo-card').className = 'kpi ' + (fluxo >= 0 ? 'good' : 'bad');

    // "Em atraso" fica de fora do filtro de competência — atraso é
    // sempre em relação a hoje, não ao mês que está sendo analisado
    // (mesma convenção do painel geral).
    const atrasadas = todasDespesas.filter(d => d.status === 'a_pagar' && d.vencimento < hoje || d.status === 'atrasado');
    document.getElementById('kpi-atraso').textContent = fmt(atrasadas.reduce((s, d) => s + Number(d.valor), 0));
    document.getElementById('kpi-atraso-delta').textContent = `${atrasadas.length} despesa${atrasadas.length === 1 ? '' : 's'}`;
  }

  // Visão consolidada: quanto cada fornecedor recebeu (ou vai receber)
  // na competência selecionada, agrupado por categoria (Funcionário,
  // Terceirizado, Contabilidade, Imposto, Software...) com subtotal por
  // categoria — o card de Despesa do mês já mostra o total geral, este
  // quebra por categoria e por fornecedor dentro dela.
  function renderCustoPorFornecedor(competencia) {
    const mes = competencia || mesAtual();
    const despesasDoMes = todasDespesas.filter(d => d.vencimento.slice(0, 7) === mes);
    const tbody = document.getElementById('custo-fornecedor-tbody');
    const tag = document.getElementById('custo-fornecedor-tag');

    if (despesasDoMes.length === 0) {
      tag.textContent = '—';
      tbody.innerHTML = '<tr><td colspan="3" style="color:var(--muted); font-style:italic; padding:24px;">Nenhuma despesa lançada neste mês ainda.</td></tr>';
      return;
    }

    const porCategoria = {};
    despesasDoMes.forEach(d => {
      const categoria = d.fornecedor_categoria || 'Sem categoria';
      if (!porCategoria[categoria]) porCategoria[categoria] = { total: 0, fornecedores: {} };
      porCategoria[categoria].total += Number(d.valor);
      if (!porCategoria[categoria].fornecedores[d.fornecedor_nome]) {
        porCategoria[categoria].fornecedores[d.fornecedor_nome] = { total: 0, qtd: 0 };
      }
      porCategoria[categoria].fornecedores[d.fornecedor_nome].total += Number(d.valor);
      porCategoria[categoria].fornecedores[d.fornecedor_nome].qtd += 1;
    });

    const categorias = Object.entries(porCategoria).sort((a, b) => b[1].total - a[1].total);
    const totalFornecedores = new Set(despesasDoMes.map(d => d.fornecedor_nome)).size;
    tag.textContent = `${categorias.length} categoria${categorias.length === 1 ? '' : 's'} · ${totalFornecedores} fornecedor${totalFornecedores === 1 ? '' : 'es'}`;

    tbody.innerHTML = categorias.map(([categoria, dadosCategoria]) => {
      const linhasFornecedor = Object.entries(dadosCategoria.fornecedores)
        .sort((a, b) => b[1].total - a[1].total)
        .map(([nome, dados]) => `
          <tr class="categoria-body">
            <td class="name">${nome}</td>
            <td style="color:var(--muted);">${dados.qtd} lançamento${dados.qtd === 1 ? '' : 's'}</td>
            <td>${fmt(dados.total)}</td>
          </tr>`).join('');
      return `
        <tr class="categoria-header">
          <td colspan="3">${categoria}<span class="categoria-total">${fmt(dadosCategoria.total)}</span></td>
        </tr>
        ${linhasFornecedor}`;
    }).join('');
  }

  function renderizar() {
    const tbody = document.getElementById('despesas-tbody');
    const competenciaSelecionada = document.getElementById('period-select').value;
    // Filtra pelo mês do VENCIMENTO (não da competência/mês de
    // referência) — mesma convenção dos KPIs acima e do painel geral,
    // é quando o dinheiro sai de verdade que importa pra essa listagem.
    let lista = todasDespesas.filter(d => d.vencimento.slice(0, 7) === competenciaSelecionada);
    if (filtroAtivo) lista = lista.filter(d => d.status === filtroAtivo);
    lista = lista.slice().sort((a, b) => b.vencimento.localeCompare(a.vencimento));

    if (lista.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state">Nenhuma despesa' + (filtroAtivo ? ' com esse status' : '') + ' com vencimento nesse mês.</div></td></tr>';
      return;
    }

    tbody.innerHTML = lista.map(d => {
      const opcoes = (proximosStatus[d.status] || [d.status]).map(s =>
        `<option value="${s}" ${s === d.status ? 'selected' : ''}>${statusLabels[s]}</option>`
      ).join('');
      const origemPill = d.origem === 'manual' ? '<span class="origem-pill">· manual</span>' : '<span class="origem-pill">· Movidesk</span>';
      const linkEditar = d.origem === 'manual'
        ? `<a href="crm-newsiga-lancar-despesa.php?id=${d.id}" style="color:var(--forest); font-weight:600; font-size:13px; text-decoration:none; margin-right:12px;">editar</a>`
        : '';
      const botaoExcluir = (d.origem === 'manual' && d.status !== 'pago')
        ? `<a href="#" class="excluir-link" data-id="${d.id}" style="color:var(--red); font-weight:600; font-size:13px; text-decoration:none;">excluir</a>`
        : '';
      return `
        <tr>
          <td class="name">${d.fornecedor_nome}<div style="color:var(--muted); font-weight:400; font-size:12.5px;">${d.contrato_descricao || ''}</div></td>
          <td>${d.competencia}</td>
          <td>${d.vencimento.split('-').reverse().join('/')}</td>
          <td>${fmt(d.valor)}${origemPill}</td>
          <td>
            <select class="status-select" data-id="${d.id}" data-atual="${d.status}">${opcoes}</select>
            <div class="row-msg" id="msg-${d.id}"></div>
          </td>
          <td>${linkEditar}${botaoExcluir}</td>
        </tr>`;
    }).join('');

    document.querySelectorAll('.excluir-link').forEach(link => {
      link.addEventListener('click', async (e) => {
        e.preventDefault();
        const despesaId = link.dataset.id;
        if (!confirm('Excluir este lançamento de despesa? Essa ação não pode ser desfeita.')) return;
        try {
          const fd = new FormData();
          fd.append('despesa_id', despesaId);
          const resp = await fetch('excluir-despesa-competencia.php', { method: 'POST', body: fd });
          const data = await resp.json();
          if (!resp.ok) { alert(data.erro || 'Erro ao excluir.'); return; }
          todasDespesas = todasDespesas.filter(d => String(d.id) !== String(despesaId));
          renderizar();
          renderKpis(document.getElementById('period-select').value);
            renderCustoPorFornecedor(document.getElementById('period-select').value);
        } catch (err) {
          alert('Falha de conexão ao excluir.');
        }
      });
    });

    document.querySelectorAll('.status-select').forEach(sel => {
      sel.addEventListener('change', async () => {
        const despesaId = sel.dataset.id;
        const novoStatus = sel.value;
        const statusAnterior = sel.dataset.atual;
        const msg = document.getElementById('msg-' + despesaId);
        sel.disabled = true;
        msg.textContent = 'Salvando...';
        msg.className = 'row-msg';

        try {
          const formData = new FormData();
          formData.append('despesa_id', despesaId);
          formData.append('status', novoStatus);
          const resp = await fetch('atualizar-status-despesa.php', { method: 'POST', body: formData });
          const data = await resp.json();

          if (!resp.ok) {
            msg.className = 'row-msg error';
            msg.textContent = data.erro || 'Erro ao salvar.';
            sel.value = statusAnterior;
          } else {
            const item = todasDespesas.find(d => String(d.id) === String(despesaId));
            if (item) item.status = novoStatus;
            renderizar();
            renderKpis(document.getElementById('period-select').value);
            renderCustoPorFornecedor(document.getElementById('period-select').value);
          }
        } catch (err) {
          msg.className = 'row-msg error';
          msg.textContent = 'Falha de conexão.';
          sel.value = statusAnterior;
        } finally {
          sel.disabled = false;
        }
      });
    });
  }

  document.getElementById('period-select').addEventListener('change', (e) => {
    renderKpis(e.target.value);
    renderCustoPorFornecedor(e.target.value);
    renderizar();
  });

  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      filtroAtivo = btn.dataset.status;
      renderizar();
    });
  });

  Promise.all([
    fetch('listar-despesas-competencia.php').then(r => r.json()).catch(() => ({ sucesso: false })),
    fetch('listar-faturas.php').then(r => r.json()).catch(() => ({ sucesso: false })),
    fetch('listar-contratos.php').then(r => r.json()).catch(() => ({ sucesso: false })),
    fetch('listar-parcelas-pendentes.php').then(r => r.json()).catch(() => ({ sucesso: false })),
  ]).then(([despesasData, faturasData, contratosData, parcelasData]) => {
    todasDespesas = (despesasData.sucesso && despesasData.despesas) ? despesasData.despesas : [];
    todasFaturas = (faturasData.sucesso && faturasData.faturas) ? faturasData.faturas : [];
    todosContratos = (contratosData.sucesso && contratosData.contratos) ? contratosData.contratos : [];
    todasParcelasPendentes = (parcelasData.sucesso && parcelasData.parcelas) ? parcelasData.parcelas : [];
    renderizar();
    renderKpis(document.getElementById('period-select').value);
            renderCustoPorFornecedor(document.getElementById('period-select').value);
  }).catch(() => {
    document.getElementById('despesas-tbody').innerHTML =
      '<tr><td colspan="6" style="color:var(--red); padding:24px;">Falha ao carregar despesas do servidor.</td></tr>';
  });
</script>
</body>
</html>
