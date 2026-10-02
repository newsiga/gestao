<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Fechamento de consultores</title>
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
  .wrap{max-width:1040px; margin:0 auto; padding:0 32px;}
  nav{border-bottom:1px solid var(--border); padding:22px 0;}
  nav .wrap{display:flex; align-items:center; justify-content:space-between; max-width:1160px;}
  .logo{font-family:var(--font-display); font-weight:800; font-size:19px; color:var(--forest); text-decoration:none;}
  .logo span{color:var(--muted); font-weight:600; font-size:13px; margin-left:8px;}
  .nav-links{display:flex; gap:22px; font-size:13.5px; color:var(--muted); font-weight:600;}
  .nav-links a{color:var(--muted); text-decoration:none;}
  .nav-links a.active{color:var(--forest);}
  .btn-nav{font-family:var(--font-ui); font-size:13px; font-weight:700; background:var(--forest); color:var(--white); padding:10px 18px; border-radius:8px; text-decoration:none;}

  .page-head{padding:44px 0 0; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:20px;}
  .eyebrow{font-family:var(--font-ui); font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:14px;}
  h1{font-family:var(--font-display); font-weight:800; font-size:32px; letter-spacing:-.01em;}
  h1 em{font-family:var(--font-italic); font-style:italic; font-weight:400;}
  .period-label{font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-bottom:8px;}
  .period-select{
    font-family:var(--font-display); font-weight:700; font-size:14.5px; color:var(--forest);
    background:var(--white); border:1px solid var(--border); border-radius:8px; padding:10px 16px; cursor:pointer;
  }

  .kpis{display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin:28px 0 24px;}
  .kpi{background:var(--white); border:1px solid var(--border); border-radius:12px; padding:20px 22px;}
  .kpi .label{font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-bottom:10px;}
  .kpi .value{font-family:var(--font-display); font-size:24px; font-weight:800;}
  .kpi .delta{font-size:12.5px; color:var(--muted); margin-top:4px;}

  .avisos{background:#f3e6c9; color:#8a6414; border-radius:10px; padding:14px 18px; font-size:13.5px; margin-bottom:16px; display:none;}
  .avisos div + div{margin-top:6px;}
  .form-msg{margin-bottom:16px; padding:14px 18px; border-radius:10px; font-size:13.5px; display:none;}
  .form-msg.ok{background:#dbe9d8; color:#2f5c3f; display:block;}
  .form-msg.error{background:#f1d9d4; color:var(--red); display:block;}
  .empty-state{background:var(--white); border:1px solid var(--border); border-radius:12px; padding:60px 22px; text-align:center; color:var(--muted); font-size:14px;}

  /* ---- Um cartão por fornecedor ---- */
  .fornecedor{background:var(--white); border:1px solid var(--border); border-radius:12px; margin-bottom:14px; overflow:hidden;}
  .fornecedor-head{display:flex; justify-content:space-between; align-items:center; gap:16px; padding:16px 22px; background:#faf9f5; border-bottom:1px solid var(--border);}
  .fornecedor-nome{font-family:var(--font-display); font-weight:800; font-size:16px;}
  .categoria{font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); background:var(--card); border-radius:20px; padding:3px 10px; margin-left:10px; vertical-align:2px;}
  .fornecedor-total{text-align:right; white-space:nowrap;}
  .fornecedor-total small{display:block; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--muted);}
  .fornecedor-total b{font-family:var(--font-display); font-weight:800; font-size:17px;}

  /* ---- Uma linha por contrato ---- */
  .linha{display:grid; grid-template-columns:22px minmax(0,1fr) 96px 168px; column-gap:20px; padding:20px 22px; border-bottom:1px solid var(--border); align-items:start;}
  .linha:last-child{border-bottom:none;}
  .linha .sel{width:18px; height:18px; margin-top:3px; cursor:pointer; accent-color:var(--forest);}
  .linha.desmarcada .conteudo, .linha.desmarcada .col-horas{opacity:.55;}

  .contrato-titulo{font-weight:600; font-size:14.5px; line-height:1.4;}
  .meta{display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-top:8px; font-size:12.5px; color:var(--muted);}
  .tag{font-size:11px; font-weight:700; border-radius:20px; padding:3px 10px; white-space:nowrap;}
  .tag.movidesk{background:#e6f5bd; color:#3d5a08;}
  .tag.fixo{background:var(--card); color:var(--forest);}
  .tag.manual{background:#f3e6c9; color:#8a6414;}

  .horas-clientes{margin-top:14px;}
  .horas-clientes .legenda{font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-bottom:8px;}
  .chips{display:flex; flex-wrap:wrap; gap:6px;}
  .chip{display:inline-flex; align-items:baseline; gap:8px; background:var(--cream); border-radius:6px; padding:5px 10px; font-size:12.5px; color:var(--forest);}
  .chip b{font-family:var(--font-display); font-weight:700;}
  .horas-clientes.informativo .chip{background:transparent; border:1px dashed var(--border); color:var(--muted);}
  .linha-aviso{margin-top:12px; font-size:12.5px; color:#8a6414;}

  .campo-label{font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-bottom:6px;}
  .col-horas .horas-valor{font-family:var(--font-display); font-weight:700; font-size:17px; line-height:42px;}
  .col-horas .horas-valor.vazio{color:var(--border);}
  .col-valor{text-align:right;}
  .linha input[type=text], .linha input[type=date]{height:42px; border-radius:8px; border:1px solid var(--border); background:var(--white); padding:0 12px; font-size:14px; font-family:var(--font-ui); color:var(--forest); width:100%;}
  .linha input:focus{outline:none; border-color:var(--forest);}
  .linha input.base{font-family:var(--font-display); font-weight:700; font-size:15.5px; text-align:right;}
  .linha input.horas-input{font-family:var(--font-display); font-weight:700; font-size:15.5px;}
  .linha-total{margin-top:8px; font-size:12.5px; color:var(--muted); display:none;}
  .linha-total b{font-family:var(--font-display); color:var(--forest);}

  /* Vencimento e adicional: segunda faixa da linha, discreta */
  .extras{grid-column:2 / -1; display:flex; flex-wrap:wrap; align-items:center; gap:10px 18px; margin-top:16px; padding-top:14px; border-top:1px dashed var(--border); font-size:12.5px; color:var(--muted);}
  .extras label{display:flex; align-items:center; gap:8px; font-weight:600;}
  .extras input[type=date]{height:34px; width:148px; font-size:13px; padding:0 10px;}
  .btn-adicional{font-family:var(--font-ui); font-size:12.5px; font-weight:700; color:var(--forest); background:none; border:none; cursor:pointer; padding:0; margin-left:auto;}
  .btn-adicional:hover{text-decoration:underline;}
  .adicional-box{flex-basis:100%; display:none; grid-template-columns:168px minmax(0,1fr); gap:12px;}
  .adicional-box.aberto{display:grid;}
  .adicional-box input.adicional{text-align:right;}

  /* Linha já lançada: só leitura, uma faixa compacta */
  .linha.lancada{grid-template-columns:22px minmax(0,1fr) auto auto; align-items:center; padding:14px 22px; color:var(--muted);}
  .linha.lancada .contrato-titulo{font-weight:500; font-size:13.5px;}
  .linha.lancada .valor-lancado{font-family:var(--font-display); font-weight:700; font-size:14.5px; color:var(--forest); white-space:nowrap;}
  .status-pill{font-family:var(--font-ui); font-size:11px; font-weight:700; padding:4px 12px; border-radius:20px; white-space:nowrap; background:#dbe9d8; color:#2f5c3f;}
  .check-lancado{color:#2f5c3f; font-weight:800;}

  .nota-rodape{font-size:12.5px; color:var(--muted); margin:6px 2px 110px;}

  /* Barra fixa no rodapé: total + botão sempre à vista */
  .barra{position:fixed; left:0; right:0; bottom:0; background:var(--forest); color:var(--white); z-index:10;}
  .barra .wrap{display:flex; justify-content:space-between; align-items:center; gap:16px; padding-top:14px; padding-bottom:14px;}
  .barra .resumo small{display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#a9b8b1;}
  .barra .resumo b{font-family:var(--font-display); font-weight:800; font-size:22px;}
  .btn-lancar{font-family:var(--font-ui); font-weight:700; font-size:14.5px; background:var(--lime); color:var(--forest); border:none; padding:14px 26px; border-radius:8px; cursor:pointer;}
  .btn-lancar:disabled{opacity:.45; cursor:not-allowed;}

  @media (max-width:860px){
    .nav-links{display:none;}
    .kpis{grid-template-columns:1fr;}
    .linha{grid-template-columns:22px minmax(0,1fr); row-gap:14px;}
    .col-horas, .col-valor{grid-column:2; text-align:left;}
    .linha input.base{text-align:left;}
    .adicional-box{grid-template-columns:1fr;}
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
    <a class="btn-nav" href="crm-newsiga-despesas.php">← Despesas</a>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div>
      <div class="eyebrow">contas a pagar</div>
      <h1>Fechamento de <em>consultores</em>.</h1>
    </div>
    <div>
      <div class="period-label">Mês trabalhado</div>
      <select class="period-select" id="period-select"></select>
    </div>
  </div>

  <div class="kpis">
    <div class="kpi">
      <div class="label">A lançar</div>
      <div class="value" id="kpi-lancar">—</div>
      <div class="delta" id="kpi-lancar-delta"></div>
    </div>
    <div class="kpi">
      <div class="label">Horas pagas por hora</div>
      <div class="value" id="kpi-horas">—</div>
      <div class="delta">apontadas no Movidesk no mês</div>
    </div>
    <div class="kpi">
      <div class="label">Já lançado no mês</div>
      <div class="value" id="kpi-lancado">—</div>
      <div class="delta" id="kpi-lancado-delta"></div>
    </div>
  </div>

  <div class="avisos" id="avisos"></div>
  <div class="form-msg" id="form-msg"></div>

  <div id="fornecedores">
    <div class="empty-state">Carregando...</div>
  </div>

  <div class="nota-rodape" id="nota-rodape"></div>
</div>

<div class="barra">
  <div class="wrap">
    <div class="resumo"><small id="resumo-qtd">Nenhuma linha marcada</small><b id="resumo-total">R$ 0,00</b></div>
    <button class="btn-lancar" id="lancar-btn" disabled>Lançar selecionados</button>
  </div>
</div>

<script>
  const fmt = (v) => 'R$ ' + Number(v).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));

  function aplicarMascaraMoeda(el) {
    let digits = el.value.replace(/\D/g, '');
    if (digits === '') { el.value = ''; return; }
    let num = (parseInt(digits, 10) / 100).toFixed(2);
    el.value = 'R$ ' + num.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d)(?=,))/g, '.');
  }
  function moedaParaDecimal(valorMascarado) {
    if (!valorMascarado) return '';
    const limpo = valorMascarado.replace(/[^\d,]/g, '').replace(/\.(?=\d{3},)/g, '');
    return limpo.replace(/\./g, '').replace(',', '.');
  }
  const moedaParaNumero = (el) => el ? (parseFloat(moedaParaDecimal(el.value)) || 0) : 0;

  // Hora decimal -> "HH:MM", como no apontamento do Movidesk (41,4667 -> 41:28)
  function fmtHoras(horasDecimal) {
    const minutosTotais = Math.round(Number(horasDecimal) * 60);
    return `${Math.floor(minutosTotais / 60)}:${String(minutosTotais % 60).padStart(2, '0')}`;
  }
  // "41:28" ou "41" -> hora decimal; qualquer outra coisa -> null
  function horasParaDecimal(texto) {
    const m = texto.trim().match(/^(\d{1,3})(?::([0-5]?\d))?$/);
    return m ? Number(m[1]) + Number(m[2] || 0) / 60 : null;
  }

  // ---- Seletor de competência: 6 meses passados + mês atual, com o mês
  // ANTERIOR pré-selecionado — o fechamento é sempre do mês que acabou.
  const nomesMesCompleto = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
  function popularSeletorCompetencia() {
    const select = document.getElementById('period-select');
    const hoje = new Date();
    const opcoes = [];
    for (let i = -6; i <= 0; i++) {
      const d = new Date(hoje.getFullYear(), hoje.getMonth() + i, 1);
      const valor = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
      const label = `${nomesMesCompleto[d.getMonth()].charAt(0).toUpperCase() + nomesMesCompleto[d.getMonth()].slice(1)} · ${d.getFullYear()}`;
      opcoes.push({ valor, label, selecionado: i === -1 });
    }
    select.innerHTML = opcoes.map(o => `<option value="${o.valor}"${o.selecionado ? ' selected' : ''}>${o.label}</option>`).join('');
  }
  popularSeletorCompetencia();

  const statusLabels = { a_pagar: 'a pagar', pago: 'pago', atrasado: 'atrasado' };
  const tagLabels = { movidesk: 'Horas do Movidesk', fixo: 'Valor fixo', manual: 'Valor digitado' };
  const valorLabels = { movidesk: 'Valor calculado', fixo: 'Valor fixo', manual: 'Valor' };
  let linhasAtuais = [];

  function htmlHorasPorCliente(l) {
    if (l.horas_por_cliente.length === 0) return '';
    const informativo = l.calculo === 'fixo';
    const chips = l.horas_por_cliente.map(h => `<span class="chip">${esc(h.cliente)}<b>${fmtHoras(h.horas)}</b></span>`).join('');
    return `
      <div class="horas-clientes${informativo ? ' informativo' : ''}">
        <div class="legenda">${informativo ? 'Horas no mês — já dentro do fixo' : 'Horas por cliente'}</div>
        <div class="chips">${chips}</div>
      </div>`;
  }

  function htmlLinha(l) {
    const taxa = l.tipo === 'hora_aberta' && l.valor_hora >= 1 ? `<span>${fmt(l.valor_hora)} por hora</span>` : '';

    if (l.despesa_existente) {
      return `
        <div class="linha lancada">
          <span class="check-lancado">✓</span>
          <div class="contrato-titulo">${esc(l.descricao)}</div>
          <span class="status-pill">lançado · ${statusLabels[l.despesa_existente.status] || l.despesa_existente.status}</span>
          <span class="valor-lancado">${fmt(l.despesa_existente.valor)}</span>
        </div>`;
    }

    // Linha sem valor sugerido nasce desmarcada — entra sozinha quando o
    // valor (ou as horas, em contrato por hora digitado) for preenchido.
    const temValor = l.valor_base !== null && l.valor_base > 0;
    const horasDigitadas = l.calculo === 'manual' && l.tipo === 'hora_aberta' && l.valor_hora >= 1;
    const colHoras = horasDigitadas
      ? `<div class="campo-label">Horas</div><input type="text" class="horas-input" inputmode="numeric" placeholder="0:00">`
      : `<div class="campo-label">Horas</div><div class="horas-valor${l.horas === null ? ' vazio' : ''}">${l.horas !== null ? fmtHoras(l.horas) : '—'}</div>`;

    return `
      <div class="linha${temValor ? '' : ' desmarcada'}" data-id="${l.contrato_fornecedor_id}">
        <input type="checkbox" class="sel"${temValor ? ' checked' : ''}>
        <div class="conteudo">
          <div class="contrato-titulo">${esc(l.descricao)}</div>
          <div class="meta"><span class="tag ${l.calculo}">${tagLabels[l.calculo]}</span>${taxa}</div>
          ${htmlHorasPorCliente(l)}
          ${l.aviso ? `<div class="linha-aviso">${esc(l.aviso)}</div>` : ''}
        </div>
        <div class="col-horas">${colHoras}</div>
        <div class="col-valor">
          <div class="campo-label">${valorLabels[l.calculo]}</div>
          <input type="text" inputmode="decimal" class="base" placeholder="R$ 0,00" value="${temValor ? fmt(l.valor_base) : ''}">
          <div class="linha-total">total <b></b></div>
        </div>
        <div class="extras">
          <label>Vencimento <input type="date" class="venc" value="${l.vencimento}"></label>
          <button type="button" class="btn-adicional">+ Adicional (despesa, comissão, projeto)</button>
          <div class="adicional-box">
            <input type="text" inputmode="decimal" class="adicional" placeholder="R$ 0,00">
            <input type="text" class="obs" maxlength="255" placeholder="Motivo — ex: atendimento presencial na Fiabesa">
          </div>
        </div>
      </div>`;
  }

  function renderizar(previa) {
    const container = document.getElementById('fornecedores');
    linhasAtuais = previa.linhas;

    const avisosEl = document.getElementById('avisos');
    avisosEl.innerHTML = previa.avisos.map(a => `<div>${esc(a)}</div>`).join('');
    avisosEl.style.display = previa.avisos.length ? 'block' : 'none';

    // Quem aponta hora e não é fornecedor (o próprio Felipe) não é um
    // problema a resolver — fica só como nota, no fim da página.
    document.getElementById('nota-rodape').textContent = previa.consultores_sem_fornecedor.length
      ? 'Fora deste fechamento (apontaram horas, mas não são fornecedores): ' + previa.consultores_sem_fornecedor.map(c => `${c.nome} ${fmtHoras(c.horas)}`).join(', ') + '.'
      : '';

    if (linhasAtuais.length === 0) {
      container.innerHTML = '<div class="empty-state">Nenhum contrato de fornecedor ativo.</div>';
      atualizarResumo();
      return;
    }

    // Consultores primeiro (é o que precisa de conferência), depois o resto
    const grupos = [];
    const indice = {};
    linhasAtuais.forEach(l => {
      if (!(l.fornecedor_id in indice)) {
        indice[l.fornecedor_id] = grupos.length;
        grupos.push({ nome: l.fornecedor_nome, categoria: l.fornecedor_categoria || 'Sem categoria', linhas: [] });
      }
      grupos[indice[l.fornecedor_id]].linhas.push(l);
    });
    const ehConsultor = (g) => g.categoria.toLowerCase().startsWith('consultor') ? 0 : 1;
    grupos.sort((a, b) => ehConsultor(a) - ehConsultor(b) || a.nome.localeCompare(b.nome, 'pt-BR'));

    container.innerHTML = grupos.map(g => `
      <section class="fornecedor">
        <div class="fornecedor-head">
          <div><span class="fornecedor-nome">${esc(g.nome)}</span><span class="categoria">${esc(g.categoria)}</span></div>
          <div class="fornecedor-total"><small>a lançar</small><b>R$ 0,00</b></div>
        </div>
        ${g.linhas.map(htmlLinha).join('')}
      </section>`).join('');

    container.querySelectorAll('.linha[data-id]').forEach(linha => {
      const base = linha.querySelector('.base');
      const adicional = linha.querySelector('.adicional');
      const sel = linha.querySelector('.sel');

      const aoMudarValor = () => {
        sel.checked = moedaParaNumero(base) + moedaParaNumero(adicional) > 0;
        atualizarResumo();
      };
      base.addEventListener('input', () => { aplicarMascaraMoeda(base); aoMudarValor(); });
      adicional.addEventListener('input', () => { aplicarMascaraMoeda(adicional); aoMudarValor(); });
      sel.addEventListener('change', atualizarResumo);

      linha.querySelector('.btn-adicional').addEventListener('click', (e) => {
        const box = linha.querySelector('.adicional-box');
        const abrir = !box.classList.contains('aberto');
        box.classList.toggle('aberto', abrir);
        e.target.textContent = abrir ? '− Remover adicional' : '+ Adicional (despesa, comissão, projeto)';
        if (abrir) {
          adicional.focus();
        } else {
          adicional.value = '';
          linha.querySelector('.obs').value = '';
          aoMudarValor();
        }
      });

      // Contrato por hora sem horas no Movidesk: digita as horas e o
      // valor sai de horas × taxa (continua editável depois).
      const horasInput = linha.querySelector('.horas-input');
      if (horasInput) {
        horasInput.addEventListener('input', () => {
          const l = linhasAtuais.find(x => String(x.contrato_fornecedor_id) === linha.dataset.id);
          const horas = horasParaDecimal(horasInput.value);
          linha.dataset.horas = horas !== null ? String(horas) : '';
          base.value = horas ? fmt(horas * l.valor_hora) : '';
          aoMudarValor();
        });
      }
    });
    atualizarResumo();
  }

  const totalDaLinha = (linha) => moedaParaNumero(linha.querySelector('.base')) + moedaParaNumero(linha.querySelector('.adicional'));
  const linhasMarcadas = () => Array.from(document.querySelectorAll('.linha[data-id]')).filter(l => l.querySelector('.sel').checked);

  function atualizarResumo() {
    // Por linha: esmaece a desmarcada e mostra o total quando há adicional
    document.querySelectorAll('.linha[data-id]').forEach(linha => {
      linha.classList.toggle('desmarcada', !linha.querySelector('.sel').checked);
      const temAdicional = moedaParaNumero(linha.querySelector('.adicional')) > 0;
      const totalEl = linha.querySelector('.linha-total');
      totalEl.style.display = temAdicional ? 'block' : 'none';
      totalEl.querySelector('b').textContent = fmt(totalDaLinha(linha));
    });

    // Por fornecedor
    document.querySelectorAll('.fornecedor').forEach(card => {
      const marcadas = Array.from(card.querySelectorAll('.linha[data-id]')).filter(l => l.querySelector('.sel').checked);
      const bloco = card.querySelector('.fornecedor-total');
      bloco.style.visibility = card.querySelector('.linha[data-id]') ? 'visible' : 'hidden';
      bloco.querySelector('b').textContent = fmt(marcadas.reduce((s, l) => s + totalDaLinha(l), 0));
    });

    // Geral
    const marcadas = linhasMarcadas();
    const total = marcadas.reduce((s, l) => s + totalDaLinha(l), 0);
    const qtd = `${marcadas.length} despesa${marcadas.length === 1 ? '' : 's'} marcada${marcadas.length === 1 ? '' : 's'}`;
    document.getElementById('resumo-qtd').textContent = marcadas.length ? qtd : 'Nenhuma linha marcada';
    document.getElementById('resumo-total').textContent = fmt(total);
    document.getElementById('lancar-btn').disabled = marcadas.length === 0;

    const lancadas = linhasAtuais.filter(l => l.despesa_existente);
    const horasPagas = linhasAtuais.filter(l => l.calculo === 'movidesk').reduce((s, l) => s + (l.horas || 0), 0);
    document.getElementById('kpi-lancar').textContent = linhasAtuais.length ? fmt(total) : '—';
    document.getElementById('kpi-lancar-delta').textContent = linhasAtuais.length ? qtd : '';
    document.getElementById('kpi-horas').textContent = linhasAtuais.length ? fmtHoras(horasPagas) : '—';
    document.getElementById('kpi-lancado').textContent = linhasAtuais.length ? fmt(lancadas.reduce((s, l) => s + l.despesa_existente.valor, 0)) : '—';
    document.getElementById('kpi-lancado-delta').textContent = linhasAtuais.length ? `${lancadas.length} despesa${lancadas.length === 1 ? '' : 's'}` : '';
  }

  let cargaAtual = 0;
  async function carregar() {
    const competencia = document.getElementById('period-select').value;
    const container = document.getElementById('fornecedores');
    const carga = ++cargaAtual;
    linhasAtuais = [];
    document.getElementById('avisos').style.display = 'none';
    document.getElementById('nota-rodape').textContent = '';
    container.innerHTML = '<div class="empty-state">Consultando as horas no Movidesk — pode levar até um minuto...</div>';
    atualizarResumo();

    try {
      const resp = await fetch(`previa-fechamento-consultores.php?competencia=${competencia}`);
      const data = await resp.json();
      if (carga !== cargaAtual) return; // a competência mudou enquanto carregava
      if (!resp.ok || !data.sucesso) {
        container.innerHTML = `<div class="empty-state" style="color:var(--red);">${esc(data.erro || 'Falha ao calcular a prévia.')}${data.detalhe ? ' ' + esc(data.detalhe) : ''}</div>`;
        return;
      }
      renderizar(data);
    } catch (err) {
      if (carga !== cargaAtual) return;
      container.innerHTML = '<div class="empty-state" style="color:var(--red);">Falha de conexão com o servidor.</div>';
    }
  }

  document.getElementById('period-select').addEventListener('change', () => {
    document.getElementById('form-msg').className = 'form-msg';
    carregar();
  });

  document.getElementById('lancar-btn').addEventListener('click', async () => {
    const btn = document.getElementById('lancar-btn');
    const msg = document.getElementById('form-msg');
    const competencia = document.getElementById('period-select').value;

    const itens = linhasMarcadas().map(linha => {
      const l = linhasAtuais.find(x => String(x.contrato_fornecedor_id) === linha.dataset.id);
      return {
        contrato_fornecedor_id: Number(linha.dataset.id),
        horas: l.calculo === 'movidesk' ? l.horas : (parseFloat(linha.dataset.horas) || null),
        valor_base: moedaParaNumero(linha.querySelector('.base')),
        valor_adicional: moedaParaNumero(linha.querySelector('.adicional')),
        observacao: linha.querySelector('.obs').value.trim(),
        vencimento: linha.querySelector('.venc').value,
        calculo: l.calculo,
      };
    });
    const total = itens.reduce((s, i) => s + i.valor_base + i.valor_adicional, 0);
    if (!confirm(`Lançar ${itens.length} despesa${itens.length === 1 ? '' : 's'} da competência ${competencia}, no total de ${fmt(total)}?`)) return;

    btn.disabled = true;
    btn.textContent = 'Lançando...';
    msg.className = 'form-msg';

    try {
      const formData = new FormData();
      formData.append('competencia', competencia);
      formData.append('itens', JSON.stringify(itens));
      const resp = await fetch('lancar-fechamento-consultores.php', { method: 'POST', body: formData });
      const data = await resp.json();

      if (!resp.ok) {
        msg.className = 'form-msg error';
        msg.textContent = (data.detalhes ? data.detalhes.join(' ') : data.erro) || 'Erro ao lançar.';
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        msg.className = 'form-msg ok';
        msg.innerHTML = `${data.lancados} despesa${data.lancados === 1 ? '' : 's'} lançada${data.lancados === 1 ? '' : 's'} como "a pagar". <a href="crm-newsiga-despesas.php" style="color:inherit; font-weight:700;">Ver em Despesas</a>`;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        carregar();
      }
    } catch (err) {
      msg.className = 'form-msg error';
      msg.textContent = 'Falha de conexão com o servidor.';
    } finally {
      btn.textContent = 'Lançar selecionados';
      atualizarResumo();
    }
  });

  carregar();
</script>
</body>
</html>
