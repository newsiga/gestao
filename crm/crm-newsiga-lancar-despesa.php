<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Lançar Despesa</title>
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
</style>
</head>
<body>

<nav>
  <div class="wrap">
    <a class="logo" href="crm-newsiga-painel.php">crm<span>.newsiga — uso interno</span></a>
    <a href="crm-newsiga-despesas.php" style="color:var(--muted); text-decoration:none; font-weight:600; font-size:13px;">← Despesas</a>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div class="eyebrow">lançamento manual — fase 1</div>
    <h1>Lançar <em>despesa</em> de uma competência.</h1>
    <p class="page-sub">Sem cálculo automático via Movidesk ainda — informe o valor já calculado (ou combinado) pra este fornecedor, neste mês.</p>
  </div>

  <form id="despesa-form">
    <div class="section">
      <div class="field">
        <label>Contrato de fornecedor</label>
        <select name="contrato_fornecedor_id" id="contrato-select" required>
          <option value="">Carregando contratos ativos...</option>
        </select>
        <div class="hint">Só contratos com status "ativo" aparecem aqui. <a href="crm-newsiga-cadastro-contrato-fornecedor.php" style="color:var(--forest); font-weight:600;">Cadastrar um novo</a>.</div>
      </div>

      <div class="row2">
        <div class="field"><label>Competência (mês de referência)</label><input type="month" name="competencia" id="competencia" required></div>
        <div class="field"><label>Vencimento</label><input type="date" name="vencimento" id="vencimento" required></div>
      </div>

      <div class="field" id="fixo-variavel-block" style="display:none;">
        <div class="row2">
          <div class="field"><label>Valor fixo do contrato (R$)</label><input type="text" id="valor-fixo-base" disabled></div>
          <div class="field"><label>Despesa variável do mês (R$) <span style="color:var(--muted); font-weight:400;">— opcional</span></label><input type="text" inputmode="decimal" class="money-input" id="despesa-variavel" placeholder="R$ 0,00"></div>
        </div>
        <div class="hint">Reembolso do mês somado ao fixo — ex: combustível, material. Some ao valor fixo do contrato pra formar o total abaixo.</div>
      </div>

      <div class="row2">
        <div class="field"><label id="valor-label">Valor (R$)</label><input type="text" inputmode="decimal" class="money-input" name="valor" id="valor" required placeholder="R$ 0,00"></div>
        <div class="field"><label>Horas consumidas <span style="color:var(--muted); font-weight:400;">— opcional</span></label><input type="number" step="0.01" name="horas_consumidas" placeholder="ex: 12.5"></div>
      </div>
    </div>

    <div class="actions">
      <button type="submit" class="btn-primary" id="submit-btn">Lançar despesa</button>
      <a href="crm-newsiga-despesas.php" class="btn-secondary">Cancelar</a>
    </div>
    <div class="form-msg" id="form-msg"></div>
  </form>
</div>

<script>
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
  document.getElementById('valor').addEventListener('input', (e) => aplicarMascaraMoeda(e.target));
  document.getElementById('despesa-variavel').addEventListener('input', (e) => { aplicarMascaraMoeda(e.target); recalcularTotalFixo(); });

  let contratosAtivos = [];

  // Contratos "mensalidade_fixa" mostram o valor fixo (do contrato) +
  // despesa variável do mês (reembolso, opcional) — o campo "Valor"
  // final vira a soma dos dois, mas continua editável à mão se precisar
  // de um ajuste pontual.
  function recalcularTotalFixo() {
    const contratoId = document.getElementById('contrato-select').value;
    const contrato = contratosAtivos.find(c => String(c.id) === String(contratoId));
    if (!contrato || contrato.tipo !== 'mensalidade_fixa') return;

    const base = Number(contrato.valor || 0);
    const variavel = parseFloat(moedaParaDecimal(document.getElementById('despesa-variavel').value)) || 0;
    const total = base + variavel;
    document.getElementById('valor').value = 'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 2});
  }

  function atualizarBlocoFixoVariavel() {
    const contratoId = document.getElementById('contrato-select').value;
    const contrato = contratosAtivos.find(c => String(c.id) === String(contratoId));
    const bloco = document.getElementById('fixo-variavel-block');
    const valorLabel = document.getElementById('valor-label');

    if (contrato && contrato.tipo === 'mensalidade_fixa') {
      bloco.style.display = 'block';
      valorLabel.textContent = 'Valor total do mês (R$) — fixo + variável';
      document.getElementById('valor-fixo-base').value = 'R$ ' + Number(contrato.valor || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2});
      document.getElementById('despesa-variavel').value = '';
      recalcularTotalFixo();
    } else {
      bloco.style.display = 'none';
      valorLabel.textContent = 'Valor (R$)';
    }
  }

  // Sugere o vencimento (mês seguinte ao da competência, no dia de
  // vencimento do contrato) assim que a competência ou o contrato mudam
  // — mesma lógica de montarDataVencimento() do fechar-competencia.php,
  // só que o usuário ainda pode ajustar a data à mão antes de salvar.
  function sugerirVencimento() {
    const contratoId = document.getElementById('contrato-select').value;
    const competencia = document.getElementById('competencia').value; // AAAA-MM
    if (!contratoId || !competencia) return;

    const contrato = contratosAtivos.find(c => String(c.id) === String(contratoId));
    if (!contrato) return;

    const [ano, mes] = competencia.split('-').map(Number);
    const mesSeguinte = mes === 12 ? 1 : mes + 1;
    const anoSeguinte = mes === 12 ? ano + 1 : ano;
    const ultimoDia = new Date(anoSeguinte, mesSeguinte, 0).getDate();
    const dia = Math.min(Number(contrato.dia_vencimento) || 5, ultimoDia);
    const vencimento = `${anoSeguinte}-${String(mesSeguinte).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
    document.getElementById('vencimento').value = vencimento;
  }
  document.getElementById('contrato-select').addEventListener('change', () => { sugerirVencimento(); atualizarBlocoFixoVariavel(); });
  document.getElementById('competencia').addEventListener('change', sugerirVencimento);

  fetch('listar-contratos-fornecedor.php')
    .then(r => r.json())
    .then(data => {
      const select = document.getElementById('contrato-select');
      contratosAtivos = ((data.sucesso && data.contratos) ? data.contratos : []).filter(c => c.status === 'ativo');
      if (contratosAtivos.length === 0) {
        select.innerHTML = '<option value="">Nenhum contrato ativo — ative um em Contratos de fornecedor</option>';
        return;
      }
      select.innerHTML = '<option value="" selected disabled>Selecione o contrato...</option>' + contratosAtivos.map(c =>
        `<option value="${c.id}">${c.fornecedor_nome} — ${c.descricao || c.tipo}</option>`
      ).join('');
    })
    .catch(() => {
      document.getElementById('contrato-select').innerHTML = '<option value="">Falha ao carregar contratos</option>';
    });

  document.getElementById('despesa-form').addEventListener('submit', async (e) => {
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

      const resp = await fetch('salvar-despesa-competencia.php', { method: 'POST', body: formData });
      const data = await resp.json();

      if (!resp.ok) {
        msg.className = 'form-msg error';
        msg.textContent = (data.detalhes ? data.detalhes.join(' ') : data.erro) || 'Erro ao salvar.';
      } else {
        msg.className = 'form-msg ok';
        msg.textContent = 'Despesa lançada com sucesso.';
        setTimeout(() => { window.location.href = 'crm-newsiga-despesas.php'; }, 900);
      }
    } catch (err) {
      msg.className = 'form-msg error';
      msg.textContent = 'Falha de conexão com o servidor.';
    } finally {
      btn.disabled = false;
      btn.textContent = 'Lançar despesa';
    }
  });
</script>
</body>
</html>
