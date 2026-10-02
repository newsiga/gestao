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
  .wrap{max-width:1280px; margin:0 auto; padding:0 32px;}
  nav{border-bottom:1px solid var(--border); padding:22px 0;}
  nav .wrap{display:flex; align-items:center; justify-content:space-between;}
  .logo{font-family:var(--font-display); font-weight:800; font-size:19px; color:var(--forest); text-decoration:none;}
  .logo span{color:var(--muted); font-weight:600; font-size:13px; margin-left:8px;}
  .nav-links{display:flex; gap:22px; font-size:13.5px; color:var(--muted); font-weight:600;}
  .nav-links a{color:var(--muted); text-decoration:none;}
  .nav-links a.active{color:var(--forest);}
  .btn-nav{font-family:var(--font-ui); font-size:13px; font-weight:700; background:var(--forest); color:var(--white); padding:10px 18px; border-radius:8px; text-decoration:none;}
  .page-head{padding:44px 0 8px;}
  .eyebrow{font-family:var(--font-ui); font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:14px;}
  h1{font-family:var(--font-display); font-weight:800; font-size:32px; letter-spacing:-.01em;}
  h1 em{font-family:var(--font-italic); font-style:italic; font-weight:400;}
  .page-sub{color:var(--muted); font-size:14.5px; margin-top:14px; max-width:78ch;}

  .period-bar{display:flex; align-items:center; gap:12px; margin:24px 0;}
  .period-label{font-size:13px; color:var(--muted); font-weight:600;}
  .period-select{
    font-family:var(--font-display); font-weight:700; font-size:14.5px; color:var(--forest);
    background:var(--white); border:1px solid var(--border); border-radius:8px; padding:10px 16px; cursor:pointer;
  }

  .avisos{background:#f3e6c9; color:#8a6414; border-radius:8px; padding:14px 16px; font-size:13.5px; margin-bottom:20px; display:none;}
  .avisos div + div{margin-top:6px;}

  .panel{background:var(--white); border:1px solid var(--border); border-radius:12px; overflow:hidden; margin-bottom:20px;}
  .table-wrap{overflow-x:auto;}
  table{width:100%; border-collapse:collapse; font-size:13.5px;}
  th{text-align:left; font-family:var(--font-ui); font-size:11.5px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; font-weight:700; padding:14px 12px; border-bottom:1px solid var(--border); white-space:nowrap;}
  td{padding:14px 12px; border-bottom:1px solid var(--border); vertical-align:top;}
  th:first-child, td:first-child{padding-left:22px;}
  th:last-child, td:last-child{padding-right:22px;}
  tr:last-child td{border-bottom:none;}
  tr.fornecedor-header td{background:var(--card); font-family:var(--font-display); font-weight:700; font-size:13.5px; padding-top:12px; padding-bottom:12px;}
  tr.fornecedor-header .categoria{color:var(--muted); font-family:var(--font-ui); font-weight:600; font-size:12px; margin-left:8px;}
  tr.lancada td{color:var(--muted);}
  td.total{font-family:var(--font-display); font-weight:700; white-space:nowrap; text-align:right;}
  th.num{text-align:right;}
  .contrato-desc{font-weight:600;}
  .detalhe{color:var(--muted); font-size:12.5px; margin-top:4px;}
  .detalhe.aviso{color:#8a6414;}
  .horas{white-space:nowrap;}

  td input[type=text], td input[type=date]{height:38px; border-radius:6px; border:1px solid var(--border); background:var(--white); padding:0 10px; font-size:13.5px; font-family:var(--font-ui); color:var(--forest);}
  td input:focus{outline:none; border-color:var(--forest);}
  td input.money{width:120px; text-align:right;}
  td input.obs{width:100%; min-width:180px;}
  td input[type=date]{width:140px;}
  td input[type=checkbox]{width:17px; height:17px; margin-top:10px; cursor:pointer;}

  .status-pill{font-family:var(--font-ui); font-size:11px; font-weight:700; padding:5px 12px; border-radius:20px; white-space:nowrap; background:#dbe9d8; color:#2f5c3f;}
  .calculo-pill{font-size:11px; font-weight:600; color:var(--muted); margin-left:6px;}

  .rodape{display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:60px;}
  .rodape .resumo{font-size:14px; color:var(--muted);}
  .rodape .resumo b{font-family:var(--font-display); font-size:20px; color:var(--forest); margin-left:8px;}
  .btn-primary{font-family:var(--font-ui); font-weight:700; font-size:14.5px; background:var(--forest); color:var(--white); border:none; padding:15px 26px; border-radius:8px; cursor:pointer;}
  .btn-primary:disabled{opacity:.6; cursor:not-allowed;}
  .form-msg{margin-bottom:20px; padding:14px 16px; border-radius:8px; font-size:13.5px; display:none;}
  .form-msg.ok{background:#dbe9d8; color:#2f5c3f; display:block;}
  .form-msg.error{background:#f1d9d4; color:var(--red); display:block;}
  .empty-state{padding:60px 22px; text-align:center; color:var(--muted); font-size:14px;}

  @media (max-width:860px){ .nav-links{display:none;} }
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
    <div class="eyebrow">contas a pagar — semi-automático</div>
    <h1>Fechamento de <em>consultores</em>.</h1>
    <p class="page-sub">As horas vêm do Movidesk e o valor é sugerido pela taxa de cada contrato. Revise, ajuste o que for exceção (despesa, comissão, participação em projeto) e lance só o que estiver marcado. Nada é gravado antes de você clicar em lançar.</p>
  </div>

  <div class="period-bar">
    <div class="period-label">Competência (mês trabalhado)</div>
    <select class="period-select" id="period-select"></select>
  </div>

  <div class="avisos" id="avisos"></div>
  <div class="form-msg" id="form-msg"></div>

  <div class="panel">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th></th><th>Contrato</th><th>Horas</th><th class="num">Valor calculado</th><th class="num">Adicional</th><th>Observação</th><th>Vencimento</th><th class="num">Total</th>
          </tr>
        </thead>
        <tbody id="previa-tbody">
          <tr><td colspan="8"><div class="empty-state">Carregando...</div></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="rodape">
    <div class="resumo"><span id="resumo-qtd">0 linhas marcadas</span><b id="resumo-total">R$ 0,00</b></div>
    <button class="btn-primary" id="lancar-btn" disabled>Lançar selecionados</button>
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
  const moedaParaNumero = (el) => parseFloat(moedaParaDecimal(el.value)) || 0;

  // Hora decimal -> "HH:MM", como no apontamento do Movidesk (41,4667 -> 41:28)
  function fmtHoras(horasDecimal) {
    const minutosTotais = Math.round(Number(horasDecimal) * 60);
    return `${Math.floor(minutosTotais / 60)}:${String(minutosTotais % 60).padStart(2, '0')}`;
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
  const calculoLabels = { movidesk: 'Movidesk', fixo: 'fixo', manual: 'manual' };
  let linhasAtuais = [];

  function detalheHoras(linha) {
    const partes = linha.horas_por_cliente.map(h => `${esc(h.cliente)} ${fmtHoras(h.horas)}`);
    if (partes.length === 0) return '';
    const sufixo = linha.calculo === 'fixo' ? ' — dentro do fixo, não pagas por hora' : '';
    return `<div class="detalhe">${partes.join(' · ')}${sufixo}</div>`;
  }

  function renderizar(previa) {
    const tbody = document.getElementById('previa-tbody');
    linhasAtuais = previa.linhas;

    const avisosEl = document.getElementById('avisos');
    const avisos = previa.avisos.slice();
    previa.consultores_sem_fornecedor.forEach(c => avisos.push(`${c.nome} apontou ${fmtHoras(c.horas)} no Movidesk e não está vinculado a nenhum fornecedor ativo — não entra neste fechamento.`));
    avisosEl.innerHTML = avisos.map(a => `<div>${esc(a)}</div>`).join('');
    avisosEl.style.display = avisos.length ? 'block' : 'none';

    if (linhasAtuais.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state">Nenhum contrato de fornecedor ativo.</div></td></tr>';
      atualizarResumo();
      return;
    }

    let html = '';
    let fornecedorAnterior = null;
    linhasAtuais.forEach(l => {
      if (l.fornecedor_id !== fornecedorAnterior) {
        fornecedorAnterior = l.fornecedor_id;
        html += `<tr class="fornecedor-header"><td colspan="8">${esc(l.fornecedor_nome)}<span class="categoria">${esc(l.fornecedor_categoria || 'Sem categoria')}</span></td></tr>`;
      }

      const taxa = l.tipo === 'hora_aberta' && l.valor_hora ? ` · ${fmt(l.valor_hora)}/h` : '';
      const contrato = `
        <div class="contrato-desc">${esc(l.descricao)}<span class="calculo-pill">· ${calculoLabels[l.calculo]}${taxa}</span></div>
        ${detalheHoras(l)}
        ${l.aviso ? `<div class="detalhe aviso">${esc(l.aviso)}</div>` : ''}`;
      const horas = l.horas !== null ? fmtHoras(l.horas) : '—';

      if (l.despesa_existente) {
        html += `
          <tr class="lancada">
            <td></td>
            <td>${contrato}</td>
            <td class="horas">${horas}</td>
            <td colspan="4">Já lançado nesta competência <span class="status-pill">${statusLabels[l.despesa_existente.status] || l.despesa_existente.status}</span></td>
            <td class="total">${fmt(l.despesa_existente.valor)}</td>
          </tr>`;
        return;
      }

      // Linha manual sem valor sugerido nasce desmarcada — só entra se o
      // valor for digitado (o campo marca a linha sozinho ao ser preenchido).
      const temValor = l.valor_base !== null && l.valor_base > 0;
      html += `
        <tr data-id="${l.contrato_fornecedor_id}">
          <td><input type="checkbox" class="sel"${temValor ? ' checked' : ''}></td>
          <td>${contrato}</td>
          <td class="horas">${horas}</td>
          <td><input type="text" inputmode="decimal" class="money base" placeholder="R$ 0,00" value="${temValor ? fmt(l.valor_base) : ''}"></td>
          <td><input type="text" inputmode="decimal" class="money adicional" placeholder="R$ 0,00"></td>
          <td><input type="text" class="obs" maxlength="255" placeholder="motivo do adicional ou do ajuste"></td>
          <td><input type="date" class="venc" value="${l.vencimento}"></td>
          <td class="total">${fmt(temValor ? l.valor_base : 0)}</td>
        </tr>`;
    });
    tbody.innerHTML = html;

    tbody.querySelectorAll('tr[data-id]').forEach(tr => {
      tr.querySelectorAll('.money').forEach(input => {
        input.addEventListener('input', () => {
          aplicarMascaraMoeda(input);
          const total = moedaParaNumero(tr.querySelector('.base')) + moedaParaNumero(tr.querySelector('.adicional'));
          tr.querySelector('.total').textContent = fmt(total);
          tr.querySelector('.sel').checked = total > 0;
          atualizarResumo();
        });
      });
      tr.querySelector('.sel').addEventListener('change', atualizarResumo);
    });
    atualizarResumo();
  }

  function linhasMarcadas() {
    return Array.from(document.querySelectorAll('#previa-tbody tr[data-id]')).filter(tr => tr.querySelector('.sel').checked);
  }

  function atualizarResumo() {
    const marcadas = linhasMarcadas();
    const total = marcadas.reduce((s, tr) => s + moedaParaNumero(tr.querySelector('.base')) + moedaParaNumero(tr.querySelector('.adicional')), 0);
    document.getElementById('resumo-qtd').textContent = `${marcadas.length} linha${marcadas.length === 1 ? '' : 's'} marcada${marcadas.length === 1 ? '' : 's'}`;
    document.getElementById('resumo-total').textContent = fmt(total);
    document.getElementById('lancar-btn').disabled = marcadas.length === 0;
  }

  let cargaAtual = 0;
  async function carregar() {
    const competencia = document.getElementById('period-select').value;
    const tbody = document.getElementById('previa-tbody');
    const carga = ++cargaAtual;
    linhasAtuais = [];
    document.getElementById('avisos').style.display = 'none';
    tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state">Consultando as horas no Movidesk — pode levar até um minuto...</div></td></tr>';
    atualizarResumo();

    try {
      const resp = await fetch(`previa-fechamento-consultores.php?competencia=${competencia}`);
      const data = await resp.json();
      if (carga !== cargaAtual) return; // a competência mudou enquanto carregava
      if (!resp.ok || !data.sucesso) {
        tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state" style="color:var(--red);">${esc(data.erro || 'Falha ao calcular a prévia.')}${data.detalhe ? ' ' + esc(data.detalhe) : ''}</div></td></tr>`;
        return;
      }
      renderizar(data);
    } catch (err) {
      if (carga !== cargaAtual) return;
      tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state" style="color:var(--red);">Falha de conexão com o servidor.</div></td></tr>';
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
    const marcadas = linhasMarcadas();

    const itens = marcadas.map(tr => {
      const linha = linhasAtuais.find(l => String(l.contrato_fornecedor_id) === tr.dataset.id);
      return {
        contrato_fornecedor_id: Number(tr.dataset.id),
        horas: linha.horas,
        valor_base: moedaParaNumero(tr.querySelector('.base')),
        valor_adicional: moedaParaNumero(tr.querySelector('.adicional')),
        observacao: tr.querySelector('.obs').value.trim(),
        vencimento: tr.querySelector('.venc').value,
        calculo: linha.calculo,
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
      } else {
        msg.className = 'form-msg ok';
        msg.innerHTML = `${data.lancados} despesa${data.lancados === 1 ? '' : 's'} lançada${data.lancados === 1 ? '' : 's'} como "a pagar". <a href="crm-newsiga-despesas.php" style="color:inherit; font-weight:700;">Ver em Despesas</a>`;
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
