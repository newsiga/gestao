<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Lançar Receita</title>
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
  .wrap{max-width:640px; margin:0 auto; padding:0 32px;}
  nav{border-bottom:1px solid var(--border); padding:22px 0;}
  nav .wrap{display:flex; align-items:center; justify-content:space-between; max-width:1160px;}
  .logo{font-family:var(--font-display); font-weight:800; font-size:19px; color:var(--forest); text-decoration:none;}
  .logo span{color:var(--muted); font-weight:600; font-size:13px; margin-left:8px;}
  .page-head{padding:44px 0 8px;}
  .eyebrow{font-family:var(--font-ui); font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:14px;}
  h1{font-family:var(--font-display); font-weight:800; font-size:32px; letter-spacing:-.01em; line-height:1.2;}
  h1 em{font-family:var(--font-italic); font-style:italic; font-weight:400;}
  .page-sub{color:var(--muted); font-size:14.5px; margin-top:14px; max-width:52ch;}
  .section{margin:36px 0;}
  .field{margin-bottom:18px;}
  .field label{display:block; font-family:var(--font-ui); font-size:13px; font-weight:600; color:var(--forest); margin-bottom:8px;}
  .field .hint{font-size:12.5px; color:var(--muted); margin-top:6px;}
  .field input, .field select{width:100%; height:46px; border-radius:8px; border:1px solid var(--border); background:var(--white); padding:0 14px; font-size:14.5px; font-family:var(--font-ui); color:var(--forest);}
  .field input:focus, .field select:focus{outline:none; border-color:var(--forest);}
  .row2{display:grid; grid-template-columns:1fr 1fr; gap:14px;}
  .actions{display:flex; gap:12px; margin-top:32px;}
  .btn-primary{font-family:var(--font-ui); font-weight:700; font-size:14.5px; background:var(--forest); color:var(--white); border:none; padding:15px 26px; border-radius:8px; cursor:pointer;}
  .btn-primary:disabled{opacity:.6; cursor:not-allowed;}
  .btn-secondary{font-family:var(--font-ui); font-weight:600; font-size:14.5px; background:transparent; color:var(--forest); border:1px solid var(--border); padding:15px 22px; border-radius:8px; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center;}
  .form-msg{margin-top:16px; padding:14px 16px; border-radius:8px; font-size:13.5px; display:none;}
  .form-msg.ok{background:#dbe9d8; color:#2f5c3f; display:block;}
  .form-msg.error{background:#f1d9d4; color:var(--red); display:block;}

  .section-head{margin:44px 0 16px; font-family:var(--font-display); font-size:18px; font-weight:800;}
  .panel{background:var(--white); border:1px solid var(--border); border-radius:12px; overflow:hidden; margin-bottom:60px;}
  table{width:100%; border-collapse:collapse; font-size:13px;}
  th{text-align:left; font-family:var(--font-ui); font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; font-weight:700; padding:12px 18px; border-bottom:1px solid var(--border);}
  td{padding:14px 18px; border-bottom:1px solid var(--border); vertical-align:middle;}
  tr:last-child td{border-bottom:none;}
  td.name{font-family:var(--font-display); font-weight:700;}
  .status-select{font-family:var(--font-ui); font-size:12px; font-weight:600; color:var(--forest); background:var(--white); border:1px solid var(--border); border-radius:6px; padding:5px 8px; cursor:pointer;}
  .row-msg{font-size:11px; color:var(--muted); margin-top:4px;}
  .row-msg.error{color:var(--red);}
  .empty-state{padding:40px 18px; text-align:center; color:var(--muted); font-size:13.5px;}
</style>
</head>
<body>

<nav>
  <div class="wrap">
    <a class="logo" href="crm-newsiga-painel.php">crm<span>.newsiga — uso interno</span></a>
    <a href="crm-newsiga-contratos.php" style="color:var(--muted); text-decoration:none; font-weight:600; font-size:13px;">← Contratos</a>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div class="eyebrow" id="eyebrow">lançamento manual de receita</div>
    <h1 id="titulo">Lançar <em>receita</em> de uma competência.</h1>
    <p class="page-sub" id="page-sub">Só pra contratos de faturamento manual (fora do ASAAS) — ex: repasse de parceiro como HubVision, MobCode. Informe o valor já combinado/recebido pra este contrato, neste mês.</p>
  </div>

  <form id="receita-form">
    <input type="hidden" name="id" id="fatura-id">
    <div class="section">
      <div class="field">
        <label>Contrato (faturamento manual)</label>
        <select name="contrato_id" id="contrato-select" required>
          <option value="">Carregando contratos...</option>
        </select>
        <div class="hint">Só contratos "ativo" com faturamento manual aparecem aqui. <a href="crm-newsiga-cadastro-contrato.php" style="color:var(--forest); font-weight:600;">Cadastrar um novo</a> (marque "Faturamento manual" no formulário).</div>
      </div>

      <div class="row2">
        <div class="field"><label>Competência (mês de referência)</label><input type="month" name="competencia" id="competencia" required></div>
        <div class="field"><label>Vencimento</label><input type="date" name="vencimento" id="vencimento" required></div>
      </div>

      <div class="row2">
        <div class="field">
          <label id="horas-label">Horas apontadas <span style="color:var(--muted); font-weight:400;">— opcional</span></label>
          <input type="text" inputmode="numeric" id="horas-consumidas-display" placeholder="HH:MM — ex: 41:28">
        </div>
        <div class="field"><label id="valor-label">Valor (R$)</label><input type="text" inputmode="decimal" class="money-input" name="valor" id="valor" required placeholder="R$ 0,00"></div>
      </div>
      <div class="hint" id="hint-calculo-horas" style="display:none; margin-top:-10px; margin-bottom:18px;">Valor calculado automaticamente (horas × R$/h do contrato) — ainda dá pra ajustar à mão, se precisar.</div>
    </div>

    <div class="actions">
      <button type="submit" class="btn-primary" id="submit-btn">Lançar receita</button>
      <a href="crm-newsiga-contratos.php" class="btn-secondary">Cancelar</a>
    </div>
    <div class="form-msg" id="form-msg"></div>
  </form>

  <div class="section-head">Receitas manuais já lançadas</div>
  <div class="panel">
    <table>
      <thead>
        <tr><th>Cliente</th><th>Competência</th><th>Vencimento</th><th>Valor</th><th>Status</th><th></th></tr>
      </thead>
      <tbody id="receitas-tbody">
        <tr><td colspan="6" class="empty-state">Carregando...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  function aplicarMascaraMoeda(el) {
    let digits = el.value.replace(/\D/g, '');
    if (digits === '') { el.value = ''; return; }
    let num = (parseInt(digits, 10) / 100).toFixed(2);
    el.value = 'R$ ' + num.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d)(?=,))/g, '.');
  }
  document.getElementById('valor').addEventListener('input', (e) => aplicarMascaraMoeda(e.target));

  // Máscara HH:MM — mesma lógica de crm-newsiga-lancar-despesa.php
  // (dígitos brutos num atributo à parte, nunca re-extraídos do texto
  // já formatado, senão o zero de preenchimento dos minutos vira dígito
  // "de verdade" na tecla seguinte).
  function renderizarHoras(el) {
    const raw = el.dataset.raw || '';
    if (raw === '') { el.value = ''; return; }
    const minutos = raw.slice(-2).padStart(2, '0');
    const horas = raw.slice(0, -2) || '0';
    el.value = `${horas}:${minutos}`;
  }
  function definirRawHoras(el, raw) {
    el.dataset.raw = raw.replace(/\D/g, '').slice(0, 6);
    renderizarHoras(el);
  }
  function horasParaDecimal(hhmm) {
    if (!hhmm || !hhmm.includes(':')) return 0;
    const [h, m] = hhmm.split(':').map(Number);
    return (h || 0) + (m || 0) / 60;
  }

  const horasDisplay = document.getElementById('horas-consumidas-display');
  horasDisplay.dataset.raw = '';
  horasDisplay.addEventListener('keydown', (e) => {
    if (/^[0-9]$/.test(e.key)) {
      e.preventDefault();
      definirRawHoras(horasDisplay, (horasDisplay.dataset.raw || '') + e.key);
      recalcularValorPorHoras();
    } else if (e.key === 'Backspace' || e.key === 'Delete') {
      e.preventDefault();
      definirRawHoras(horasDisplay, (horasDisplay.dataset.raw || '').slice(0, -1));
      recalcularValorPorHoras();
    } else if (!['Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) {
      e.preventDefault();
    }
  });

  let contratosManuais = [];

  function recalcularValorPorHoras() {
    const contratoId = document.getElementById('contrato-select').value;
    const contrato = contratosManuais.find(c => String(c.id) === String(contratoId));
    if (!contrato || contrato.tipo !== 'hora_aberta') return;

    const horas = horasParaDecimal(horasDisplay.value);
    const total = horas * Number(contrato.valor_hora || 0);
    document.getElementById('valor').value = 'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  }

  function atualizarCamposPorContrato() {
    const contratoId = document.getElementById('contrato-select').value;
    const contrato = contratosManuais.find(c => String(c.id) === String(contratoId));
    const hintHoras = document.getElementById('hint-calculo-horas');
    const valorLabel = document.getElementById('valor-label');

    if (contrato && contrato.tipo === 'hora_aberta') {
      hintHoras.style.display = 'block';
      valorLabel.textContent = `Valor (R$) — R$ ${Number(contrato.valor_hora || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}/h`;
      definirRawHoras(horasDisplay, '');
      document.getElementById('valor').value = '';
    } else if (contrato && contrato.tipo === 'mensalidade_fixa') {
      hintHoras.style.display = 'none';
      valorLabel.textContent = 'Valor (R$)';
      document.getElementById('valor').value = 'R$ ' + Number(contrato.valor || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    } else {
      hintHoras.style.display = 'none';
      valorLabel.textContent = 'Valor (R$)';
    }
  }

  function sugerirVencimento() {
    const contratoId = document.getElementById('contrato-select').value;
    const competencia = document.getElementById('competencia').value;
    if (!contratoId || !competencia) return;
    const contrato = contratosManuais.find(c => String(c.id) === String(contratoId));
    if (!contrato) return;

    const [ano, mes] = competencia.split('-').map(Number);
    const mesSeguinte = mes === 12 ? 1 : mes + 1;
    const anoSeguinte = mes === 12 ? ano + 1 : ano;
    const ultimoDia = new Date(anoSeguinte, mesSeguinte, 0).getDate();
    const dia = Math.min(Number(contrato.dia_vencimento) || 5, ultimoDia);
    document.getElementById('vencimento').value = `${anoSeguinte}-${String(mesSeguinte).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
  }
  document.getElementById('contrato-select').addEventListener('change', () => { sugerirVencimento(); atualizarCamposPorContrato(); });
  document.getElementById('competencia').addEventListener('change', sugerirVencimento);

  const faturaIdEditando = new URLSearchParams(window.location.search).get('id');
  const ehEdicao = !!faturaIdEditando;

  fetch('listar-contratos.php')
    .then(r => r.json())
    .then(data => {
      const select = document.getElementById('contrato-select');
      contratosManuais = ((data.sucesso && data.contratos) ? data.contratos : [])
        .filter(c => c.status === 'ativo' && c.faturamento_gerenciado_por === 'manual');

      if (ehEdicao) return; // modo edição preenche o select sozinho, abaixo

      if (contratosManuais.length === 0) {
        select.innerHTML = '<option value="">Nenhum contrato de faturamento manual ativo — cadastre um</option>';
        return;
      }
      select.innerHTML = '<option value="" selected disabled>Selecione o contrato...</option>' + contratosManuais.map(c =>
        `<option value="${c.id}">${c.cliente_nome} — ${c.descricao || c.tipo}</option>`
      ).join('');
    })
    .catch(() => {
      document.getElementById('contrato-select').innerHTML = '<option value="">Falha ao carregar contratos</option>';
    });

  if (ehEdicao) {
    document.getElementById('fatura-id').value = faturaIdEditando;
    document.getElementById('eyebrow').textContent = 'editar lançamento';
    document.getElementById('titulo').innerHTML = 'Editando uma receita <em>já lançada</em>.';
    document.getElementById('page-sub').textContent = 'O contrato não muda aqui — pra mover pra outro contrato, exclua e lance de novo.';
    document.getElementById('submit-btn').textContent = 'Salvar alterações';

    fetch(`buscar-fatura-manual.php?id=${faturaIdEditando}`)
      .then(r => r.json())
      .then(data => {
        if (!data.sucesso) { alert(data.erro || 'Fatura não encontrada.'); return; }
        const f = data.fatura;

        const select = document.getElementById('contrato-select');
        select.innerHTML = `<option value="${f.contrato_id}" selected>${f.cliente_nome} — ${f.contrato_descricao || f.contrato_tipo}</option>`;
        select.disabled = true;

        if (!contratosManuais.some(c => String(c.id) === String(f.contrato_id))) {
          contratosManuais.push({ id: f.contrato_id, tipo: f.contrato_tipo, valor_hora: f.valor_hora });
        }

        document.getElementById('competencia').value = f.competencia;
        document.getElementById('vencimento').value = f.vencimento;
        document.getElementById('valor').value = 'R$ ' + Number(f.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        if (f.contrato_tipo === 'hora_aberta') {
          document.getElementById('hint-calculo-horas').style.display = 'block';
          document.getElementById('valor-label').textContent = `Valor (R$) — R$ ${Number(f.valor_hora || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}/h`;
        }
      })
      .catch(() => alert('Falha ao carregar dados da fatura.'));
  }

  function moedaParaDecimal(valorMascarado) {
    if (!valorMascarado) return '';
    const limpo = valorMascarado.replace(/[^\d,]/g, '').replace(/\.(?=\d{3},)/g, '');
    return limpo.replace(/\./g, '').replace(',', '.');
  }

  // ---- Listagem das receitas manuais já lançadas ----
  const fmt = (v) => 'R$ ' + Number(v).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const statusLabelsReceita = { confirmado: 'Confirmado', pago: 'Pago', atrasado: 'Atrasado' };
  const proximosStatusReceita = {
    confirmado: ['confirmado', 'pago', 'atrasado'],
    atrasado: ['atrasado', 'pago'],
    pago: ['pago', 'confirmado'],
  };
  let todasReceitasManuais = [];

  function renderizarReceitas() {
    const tbody = document.getElementById('receitas-tbody');
    if (todasReceitasManuais.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="empty-state">Nenhuma receita manual lançada ainda.</td></tr>';
      return;
    }
    const lista = todasReceitasManuais.slice().sort((a, b) => b.vencimento.localeCompare(a.vencimento));
    tbody.innerHTML = lista.map(f => {
      const opcoes = (proximosStatusReceita[f.status] || [f.status]).map(s =>
        `<option value="${s}" ${s === f.status ? 'selected' : ''}>${statusLabelsReceita[s] || s}</option>`
      ).join('');
      const botaoExcluir = f.status !== 'pago'
        ? `<a href="#" class="excluir-link" data-id="${f.id}" style="color:var(--red); font-weight:600; font-size:12px; text-decoration:none; margin-left:10px;">excluir</a>`
        : '';
      return `
        <tr>
          <td class="name">${f.cliente_nome}<div style="color:var(--muted); font-weight:400; font-size:12px;">${f.contrato_descricao || ''}</div></td>
          <td>${f.competencia}</td>
          <td>${f.vencimento.split('-').reverse().join('/')}</td>
          <td>${fmt(f.valor)}</td>
          <td>
            <select class="status-select" data-id="${f.id}" data-atual="${f.status}">${opcoes}</select>
            <div class="row-msg" id="msg-receita-${f.id}"></div>
          </td>
          <td><a href="crm-newsiga-lancar-receita.php?id=${f.id}" style="color:var(--forest); font-weight:600; font-size:12px; text-decoration:none;">editar</a>${botaoExcluir}</td>
        </tr>`;
    }).join('');

    document.querySelectorAll('.excluir-link').forEach(link => {
      link.addEventListener('click', async (ev) => {
        ev.preventDefault();
        const id = link.dataset.id;
        if (!confirm('Excluir este lançamento de receita? Essa ação não pode ser desfeita.')) return;
        try {
          const fd = new FormData();
          fd.append('fatura_id', id);
          const resp = await fetch('excluir-fatura-manual.php', { method: 'POST', body: fd });
          const data = await resp.json();
          if (!resp.ok) { alert(data.erro || 'Erro ao excluir.'); return; }
          todasReceitasManuais = todasReceitasManuais.filter(f => String(f.id) !== String(id));
          renderizarReceitas();
        } catch (err) {
          alert('Falha de conexão ao excluir.');
        }
      });
    });

    document.querySelectorAll('.status-select').forEach(sel => {
      sel.addEventListener('change', async () => {
        const id = sel.dataset.id;
        const novoStatus = sel.value;
        const statusAnterior = sel.dataset.atual;
        const msg = document.getElementById('msg-receita-' + id);
        sel.disabled = true;
        msg.className = 'row-msg';
        msg.textContent = 'Salvando...';
        try {
          const fd = new FormData();
          fd.append('fatura_id', id);
          fd.append('status', novoStatus);
          const resp = await fetch('atualizar-status-fatura.php', { method: 'POST', body: fd });
          const data = await resp.json();
          if (!resp.ok) {
            msg.className = 'row-msg error';
            msg.textContent = data.erro || 'Erro ao salvar.';
            sel.value = statusAnterior;
          } else {
            const item = todasReceitasManuais.find(f => String(f.id) === String(id));
            if (item) item.status = novoStatus;
            renderizarReceitas();
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

  fetch('listar-faturas.php')
    .then(r => r.json())
    .then(data => {
      todasReceitasManuais = ((data.sucesso && data.faturas) ? data.faturas : [])
        .filter(f => f.faturamento_gerenciado_por === 'manual');
      renderizarReceitas();
    })
    .catch(() => {
      document.getElementById('receitas-tbody').innerHTML = '<tr><td colspan="6" style="color:var(--red);" class="empty-state">Falha ao carregar receitas.</td></tr>';
    });

  document.getElementById('receita-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    const msg = document.getElementById('form-msg');
    btn.disabled = true;
    btn.textContent = 'Salvando...';
    msg.className = 'form-msg';

    try {
      const valorEl = document.getElementById('valor');
      const valorOriginal = valorEl.value;
      valorEl.value = moedaParaDecimal(valorEl.value);

      const formData = new FormData(e.target);
      valorEl.value = valorOriginal;

      const endpoint = ehEdicao ? 'atualizar-fatura-manual.php' : 'salvar-receita-manual.php';
      const resp = await fetch(endpoint, { method: 'POST', body: formData });
      const data = await resp.json();

      if (!resp.ok) {
        msg.className = 'form-msg error';
        msg.textContent = (data.detalhes ? data.detalhes.join(' ') : data.erro) || 'Erro ao salvar.';
      } else {
        msg.className = 'form-msg ok';
        msg.textContent = ehEdicao ? 'Receita atualizada com sucesso.' : 'Receita lançada com sucesso.';
        setTimeout(() => { window.location.href = 'crm-newsiga-contratos.php'; }, 900);
      }
    } catch (err) {
      msg.className = 'form-msg error';
      msg.textContent = 'Falha de conexão com o servidor.';
    } finally {
      btn.disabled = false;
      btn.textContent = ehEdicao ? 'Salvar alterações' : 'Lançar receita';
    }
  });
</script>
</body>
</html>
