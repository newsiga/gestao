<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Novo Contrato de Fornecedor</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Instrument+Serif:ital@1&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --cream:#f3f2ec; --forest:#0d2b22; --forest-soft:#123028; --lime:#b6ef2a;
    --muted:#6b7770; --card:#e7e6e0; --card-hover:#dedcd4; --border:#d9d8d1; --white:#ffffff;
    --red:#c0503e;
    --font-display:'Manrope', system-ui, sans-serif;
    --font-italic:'Instrument Serif', serif;
    --font-ui:'Inter', system-ui, sans-serif;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  body{background:var(--cream); color:var(--forest); font-family:var(--font-ui); line-height:1.5;}
  .wrap{max-width:1080px; margin:0 auto; padding:0 32px;}
  nav{border-bottom:1px solid var(--border); padding:22px 0;}
  nav .wrap{display:flex; align-items:center; justify-content:space-between;}
  .logo{font-family:var(--font-display); font-weight:800; font-size:19px; color:var(--forest); text-decoration:none;}
  .logo span{color:var(--muted); font-weight:600; font-size:13px; margin-left:8px;}
  .breadcrumb{font-family:var(--font-ui); font-size:13px; color:var(--muted);}
  .breadcrumb b{color:var(--forest); font-weight:600;}
  .page-head{padding:44px 0 8px;}
  .eyebrow{font-family:var(--font-ui); font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:14px;}
  h1{font-family:var(--font-display); font-weight:800; font-size:36px; letter-spacing:-.01em; line-height:1.15;}
  h1 em{font-family:var(--font-italic); font-style:italic; font-weight:400;}
  .page-sub{color:var(--muted); font-size:15px; margin-top:14px; max-width:56ch;}
  .layout{display:grid; grid-template-columns:1.5fr 1fr; gap:28px; margin:40px 0 80px; align-items:start;}
  .section{margin-bottom:36px;}
  .section-label{font-family:var(--font-ui); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); margin-bottom:14px; display:block;}
  .type-grid{display:grid; grid-template-columns:1fr 1fr; gap:12px;}
  .type-card{background:var(--card); border:2px solid transparent; border-radius:10px; padding:18px 18px; cursor:pointer; transition:all .15s;}
  .type-card:hover{background:var(--card-hover);}
  .type-card.selected{background:var(--forest); border-color:var(--forest);}
  .type-card h4{font-family:var(--font-display); font-weight:700; font-size:15px; margin-bottom:6px;}
  .type-card.selected h4{color:var(--white);}
  .type-card p{font-size:13px; color:var(--muted);}
  .type-card.selected p{color:#b8c4be;}
  .field{margin-bottom:18px;}
  .field label{display:block; font-family:var(--font-ui); font-size:13px; font-weight:600; color:var(--forest); margin-bottom:8px;}
  .field .hint{font-size:12.5px; color:var(--muted); margin-top:6px;}
  .field input, .field select{width:100%; height:46px; border-radius:8px; border:1px solid var(--border); background:var(--white); padding:0 14px; font-size:14.5px; font-family:var(--font-ui); color:var(--forest);}
  .field input:focus, .field select:focus{outline:none; border-color:var(--forest);}
  .row2{display:grid; grid-template-columns:1fr 1fr; gap:14px;}
  .type-fields{display:none;}
  .type-fields.active{display:block;}
  .actions{display:flex; gap:12px; margin-top:32px;}
  .btn-primary{font-family:var(--font-ui); font-weight:700; font-size:14.5px; background:var(--forest); color:var(--white); border:none; padding:15px 26px; border-radius:8px; cursor:pointer;}
  .btn-primary:disabled{opacity:.6; cursor:not-allowed;}
  .btn-secondary{font-family:var(--font-ui); font-weight:600; font-size:14.5px; background:transparent; color:var(--forest); border:1px solid var(--border); padding:15px 22px; border-radius:8px; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center;}
  .form-msg{margin-top:16px; padding:14px 16px; border-radius:8px; font-size:13.5px; display:none;}
  .form-msg.ok{background:#dbe9d8; color:#2f5c3f; display:block;}
  .form-msg.error{background:#f1d9d4; color:var(--red); display:block;}
  .summary-card{background:var(--forest-soft); border-radius:14px; padding:30px; position:sticky; top:24px;}
  .summary-eyebrow{font-family:var(--font-ui); font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--lime); margin-bottom:16px;}
  .summary-card h3{font-family:var(--font-display); color:var(--white); font-size:20px; font-weight:700; margin-bottom:22px; line-height:1.3;}
  @media (max-width:860px){.layout{grid-template-columns:1fr;} .type-grid,.row2{grid-template-columns:1fr;} h1{font-size:28px;} .summary-card{position:static;}}
</style>
</head>
<body>

<nav>
  <div class="wrap">
    <a class="logo" href="crm-newsiga-painel.php">crm<span>.newsiga — uso interno</span></a>
    <div class="breadcrumb" style="display:flex; align-items:center; gap:24px;">
      <a href="crm-newsiga-fornecedores.php" style="color:var(--muted); text-decoration:none; font-weight:600;">Fornecedores</a>
      <a href="crm-newsiga-contratos-fornecedor.php" style="color:var(--muted); text-decoration:none; font-weight:600;">Contratos de fornecedor</a>
      <span style="color:var(--muted);">/ <b style="color:var(--forest);">Novo contrato</b></span>
    </div>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div class="eyebrow">cadastro de contrato de fornecedor</div>
    <h1>Quanto e <em>como</em> a Newsiga paga.</h1>
    <p class="page-sub">Cada fornecedor pode ter mais de um contrato ativo ao mesmo tempo — um por relação/cliente (ex: fixo mensal + repasse de um cliente + repasse de outro). Este formulário grava um contrato novo, em rascunho.</p>
  </div>

  <form id="contrato-form">
    <div class="layout">
      <div>
        <div class="section">
          <span class="section-label">Fornecedor</span>
          <select name="fornecedor_id" id="fornecedor-select" required class="field" style="width:100%; height:46px; border-radius:10px; border:1px solid var(--border); background:var(--white); padding:0 14px; font-size:14.5px; font-family:var(--font-ui); color:var(--forest);">
            <option value="">Carregando fornecedores...</option>
          </select>
          <div class="hint" style="margin-top:8px;">Não encontrou? <a href="crm-newsiga-cadastro-fornecedor.php" style="color:var(--forest); font-weight:600;">cadastrar novo fornecedor</a>.</div>
        </div>

        <div class="section">
          <span class="section-label">Tipo de contrato</span>
          <input type="hidden" name="tipo" id="tipo-input" value="mensalidade_fixa">
          <div class="type-grid">
            <div class="type-card selected" data-tipo="mensalidade_fixa"><h4>Valor fixo</h4><p>Valor mensal fixo, sem lógica de consumo.</p></div>
            <div class="type-card" data-tipo="hora_aberta"><h4>Hora aberta</h4><p>Sem banco, sem excedente — só consumo × taxa única.</p></div>
            <div class="type-card" data-tipo="banco_horas_minimo"><h4>Banco com mínimo</h4><p>Mínimo garantido pago sempre; excedente em taxa própria.</p></div>
            <div class="type-card" data-tipo="banco_horas_consumo"><h4>Banco sem mínimo</h4><p>Paga só o consumido dentro do banco; excedente em taxa própria.</p></div>
          </div>
        </div>

        <div class="section">
          <span class="section-label">Condições do contrato</span>

          <div class="type-fields active" data-for="mensalidade_fixa">
            <div class="row2">
              <div class="field"><label>Valor mensal (R$)</label><input type="text" inputmode="decimal" class="money-input" name="valor" placeholder="R$ 0,00"></div>
              <div class="field"><label>Dia de vencimento</label><input type="number" min="1" max="31" name="dia_vencimento" placeholder="5"></div>
            </div>
          </div>

          <div class="type-fields" data-for="hora_aberta">
            <div class="row2">
              <div class="field"><label>Valor por hora (R$)</label><input type="text" inputmode="decimal" class="money-input" name="valor_hora" placeholder="R$ 0,00"></div>
              <div class="field"><label>Dia de vencimento</label><input type="number" min="1" max="31" name="dia_vencimento" placeholder="5"></div>
            </div>
          </div>

          <div class="type-fields" data-for="banco_horas_consumo">
            <div class="row2">
              <div class="field"><label>Tamanho do banco (horas)</label><input type="number" step="0.01" name="horas_banco" placeholder="100"></div>
              <div class="field"><label>Dia de vencimento</label><input type="number" min="1" max="31" name="dia_vencimento" placeholder="5"></div>
            </div>
            <div class="row2">
              <div class="field"><label>Valor por hora — dentro do banco (R$)</label><input type="text" inputmode="decimal" class="money-input" name="valor_hora" placeholder="R$ 0,00"></div>
              <div class="field"><label>Valor por hora — excedente ao banco (R$)</label><input type="text" inputmode="decimal" class="money-input" name="valor_hora_excedente" placeholder="R$ 0,00"></div>
            </div>
          </div>

          <div class="type-fields" data-for="banco_horas_minimo">
            <div class="row2">
              <div class="field"><label>Valor mínimo garantido (R$)</label><input type="text" inputmode="decimal" class="money-input" name="valor" placeholder="R$ 0,00"></div>
              <div class="field"><label>Quantidade de horas do pacote <span style="color:var(--muted); font-weight:400;">— necessário se houver excedente</span></label><input type="number" step="0.01" name="horas_minimas" placeholder="8"></div>
            </div>
            <div class="field"><label>Valor por hora excedente (R$) <span style="color:var(--muted); font-weight:400;">— opcional</span></label><input type="text" inputmode="decimal" class="money-input" name="valor_hora" placeholder="R$ 0,00 (opcional)"></div>
            <div class="field">
              <label>Dia de vencimento</label>
              <input type="number" min="1" max="31" name="dia_vencimento" value="5" style="max-width:120px;">
            </div>
          </div>
        </div>

        <div class="section">
          <span class="section-label">Detalhes</span>
          <div class="field">
            <label>Descrição do contrato</label>
            <input type="text" name="descricao" placeholder="ex: Fixo mensal, Repasse MobCode/TOPPUS, Repasse HubVision — TOTVS...">
            <div class="hint">Se este contrato for um repasse de um cliente específico (ex: Robson recebendo por horas do MobCode ou da HubVision), identifique o cliente aqui — o valor/hora é o que <b>o fornecedor recebe</b>, não o que a Newsiga cobra do cliente.</div>
          </div>
        </div>

        <div class="actions">
          <button type="submit" class="btn-primary" id="submit-btn">Salvar contrato ↗</button>
          <a href="crm-newsiga-contratos-fornecedor.php" class="btn-secondary">Cancelar</a>
        </div>
        <div class="form-msg" id="form-msg"></div>
      </div>

      <div>
        <div class="summary-card">
          <div class="summary-eyebrow">resumo</div>
          <h3>O que será gravado no banco ao salvar</h3>
          <div style="font-size:13.5px; color:#dfe6e2; line-height:1.7;">
            Um novo registro em <b style="color:var(--lime);">contratos_fornecedor</b>, vinculado ao fornecedor selecionado, sempre em <b style="color:var(--lime);">status = 'rascunho'</b>. Ative o contrato na tela de listagem quando estiver pronto pra entrar no lançamento mensal de despesas.
          </div>
        </div>
      </div>
    </div>
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
  document.querySelectorAll('.money-input').forEach(el => el.addEventListener('input', () => aplicarMascaraMoeda(el)));

  const fornecedorIdPreSelecionado = new URLSearchParams(window.location.search).get('fornecedor_id');

  fetch('listar-fornecedores.php')
    .then(r => r.json())
    .then(data => {
      const select = document.getElementById('fornecedor-select');
      if (!data.sucesso || !data.fornecedores || data.fornecedores.length === 0) {
        select.innerHTML = '<option value="">Nenhum fornecedor cadastrado ainda</option>';
        return;
      }
      select.innerHTML = '<option value="" selected disabled>Selecione o fornecedor...</option>' + data.fornecedores.map(f =>
        `<option value="${f.id}">${f.nome} (${f.tipo})</option>`
      ).join('');
      if (fornecedorIdPreSelecionado) {
        select.value = fornecedorIdPreSelecionado;
      }
    })
    .catch(() => {
      document.getElementById('fornecedor-select').innerHTML = '<option value="">Falha ao carregar fornecedores</option>';
    });

  document.querySelectorAll('.type-card').forEach(card => {
    card.addEventListener('click', () => {
      document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      const tipo = card.dataset.tipo;
      document.getElementById('tipo-input').value = tipo;
      document.querySelectorAll('.type-fields').forEach(f => {
        const isActive = f.dataset.for === tipo;
        f.classList.toggle('active', isActive);
        f.querySelectorAll('input').forEach(input => { input.disabled = !isActive; });
      });
    });
  });
  document.querySelectorAll('.type-fields:not(.active) input').forEach(input => { input.disabled = true; });

  document.getElementById('contrato-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    const msg = document.getElementById('form-msg');
    btn.disabled = true;
    btn.textContent = 'Salvando...';
    msg.className = 'form-msg';

    try {
      const camposMoeda = Array.from(document.querySelectorAll('.money-input')).filter(el => !el.disabled);
      const valoresOriginais = camposMoeda.map(el => el.value);
      camposMoeda.forEach(el => { el.value = moedaParaDecimal(el.value); });

      const formData = new FormData(e.target);

      camposMoeda.forEach((el, i) => { el.value = valoresOriginais[i]; });
      const resp = await fetch('salvar-contrato-fornecedor.php', { method: 'POST', body: formData });
      const data = await resp.json();

      if (!resp.ok) {
        msg.className = 'form-msg error';
        msg.textContent = (data.detalhes ? data.detalhes.join(' ') : data.erro) || 'Erro ao salvar.';
      } else {
        msg.className = 'form-msg ok';
        msg.textContent = `Contrato de fornecedor #${data.contrato_fornecedor_id} salvo.`;
        setTimeout(() => { window.location.href = 'crm-newsiga-contratos-fornecedor.php'; }, 1000);
      }
    } catch (err) {
      msg.className = 'form-msg error';
      msg.textContent = 'Falha de conexão com o servidor.';
    } finally {
      btn.disabled = false;
      btn.textContent = 'Salvar contrato ↗';
    }
  });
</script>
</body>
</html>
