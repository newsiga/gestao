<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Novo Prospect</title>
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
  nav .wrap{max-width:1080px; display:flex; align-items:center; justify-content:space-between;}
  .logo{font-family:var(--font-display); font-weight:800; font-size:19px; color:var(--forest); text-decoration:none;}
  .logo span{color:var(--muted); font-weight:600; font-size:13px; margin-left:8px;}
  .breadcrumb{font-family:var(--font-ui); font-size:13px; color:var(--muted);}
  .breadcrumb a{color:var(--muted); text-decoration:none; font-weight:600;}
  .breadcrumb b{color:var(--forest); font-weight:600;}
  .page-head{padding:44px 0 8px;}
  .eyebrow{font-family:var(--font-ui); font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:14px;}
  h1{font-family:var(--font-display); font-weight:800; font-size:32px; letter-spacing:-.01em; line-height:1.2;}
  h1 em{font-family:var(--font-italic); font-style:italic; font-weight:400;}
  .page-sub{color:var(--muted); font-size:15px; margin-top:14px;}
  .field{margin:24px 0 18px;}
  .field label{display:block; font-family:var(--font-ui); font-size:13px; font-weight:600; margin-bottom:8px;}
  .field input, .field select, .field textarea{width:100%; border-radius:8px; border:1px solid var(--border); background:var(--white); padding:0 14px; font-size:14.5px; font-family:var(--font-ui); color:var(--forest);}
  .field input, .field select{height:46px;}
  .field textarea{padding:12px 14px; min-height:110px; resize:vertical; font-family:var(--font-ui); line-height:1.5;}
  .field input:focus, .field select:focus, .field textarea:focus{outline:none; border-color:var(--forest);}
  .field .hint{font-size:12.5px; color:var(--muted); margin-top:6px;}
  .row2{display:grid; grid-template-columns:1fr 1fr; gap:16px;}
  .checkbox-row{display:flex; align-items:center; gap:10px; margin:18px 0;}
  .checkbox-row input{width:18px; height:18px;}
  .checkbox-row label{font-size:14px; font-weight:600; margin:0;}
  .actions{display:flex; gap:12px; margin-top:20px; margin-bottom:60px; align-items:center;}
  .btn-primary{font-family:var(--font-ui); font-weight:700; font-size:14.5px; background:var(--forest); color:var(--white); border:none; padding:15px 26px; border-radius:8px; cursor:pointer;}
  .btn-primary:disabled{opacity:.6; cursor:not-allowed;}
  .btn-secondary{font-family:var(--font-ui); font-weight:600; font-size:14.5px; background:transparent; color:var(--forest); border:1px solid var(--border); padding:15px 22px; border-radius:8px; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center;}
  .btn-excluir{font-family:var(--font-ui); font-weight:600; font-size:13.5px; color:var(--red); background:none; border:none; cursor:pointer; text-decoration:underline;}
  .form-msg{margin-top:16px; padding:14px 16px; border-radius:8px; font-size:13.5px; display:none;}
  .form-msg.ok{background:#dbe9d8; color:#2f5c3f; display:block;}
  .form-msg.error{background:#f1d9d4; color:var(--red); display:block;}
</style>
</head>
<body>

<nav>
  <div class="wrap">
    <a class="logo" href="crm-newsiga-painel.php">crm<span>.newsiga — uso interno</span></a>
    <div class="breadcrumb" style="display:flex; align-items:center; gap:24px;">
      <a href="crm-newsiga-painel.php">Painel</a>
      <a href="crm-newsiga-prospects.php">Prospects</a>
      <span>/ <b id="breadcrumb-atual">Novo prospect</b></span>
    </div>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div class="eyebrow" id="eyebrow">novo prospect</div>
    <h1 id="titulo">Um negócio novo <em>no radar</em>.</h1>
    <p class="page-sub">Empresa em prospecção — ainda não é cliente. Assim que fechar, cadastra ela normal em "Clientes" e cria o contrato.</p>
  </div>

  <form id="prospect-form">
    <input type="hidden" name="id" id="prospect-id">
    <div class="field">
      <label>Empresa</label>
      <input type="text" name="nome" required placeholder="ex: EC Vitória">
    </div>
    <div class="row2">
      <div class="field">
        <label>Contato</label>
        <input type="text" name="contato" placeholder="ex: Marcos Andrade">
      </div>
      <div class="field">
        <label>Telefone</label>
        <input type="text" name="telefone" id="telefone-input" placeholder="(81) 99999-9999" maxlength="15">
      </div>
    </div>
    <div class="field">
      <label>E-mail</label>
      <input type="email" name="email" placeholder="contato@empresa.com.br">
    </div>
    <div class="field">
      <label>O que está sendo negociado</label>
      <input type="text" name="descricao" placeholder="ex: auditoria de licenças TOTVS + implantação Protheus">
    </div>
    <div class="row2">
      <div class="field">
        <label>Estágio</label>
        <select name="estagio" id="estagio-select">
          <option value="contato_inicial">Contato inicial</option>
          <option value="em_negociacao">Em negociação</option>
          <option value="proposta_enviada">Proposta enviada</option>
          <option value="ganho">Ganho</option>
          <option value="perdido">Perdido</option>
        </select>
      </div>
      <div class="field">
        <label>Valor estimado (R$)</label>
        <input type="text" inputmode="decimal" class="money-input" name="valor_estimado" placeholder="R$ 0,00">
      </div>
    </div>
    <div class="field">
      <label>Próximo contato</label>
      <input type="date" name="proximo_contato">
      <div class="hint">Quando você precisa retomar essa conversa — aparece nos Alertas do painel.</div>
    </div>
    <div class="field">
      <label>Observações — histórico da negociação</label>
      <textarea name="observacoes" placeholder="Registre aqui as últimas atualizações: contatos feitos, próximos passos, pendências..."></textarea>
    </div>
    <div class="checkbox-row">
      <input type="checkbox" name="prioritario" id="prioritario-check">
      <label for="prioritario-check">Prioritário — destacar no topo do pipeline</label>
    </div>

    <div class="actions">
      <button type="submit" class="btn-primary" id="submit-btn">Salvar prospect ↗</button>
      <a class="btn-secondary" href="crm-newsiga-prospects.php">Voltar</a>
      <button type="button" class="btn-excluir" id="excluir-btn" style="display:none; margin-left:auto;">excluir prospect</button>
    </div>
    <div class="form-msg" id="form-msg"></div>
  </form>
</div>

<script>
  // ---- Máscara de telefone: (00) 00000-0000 ou (00) 0000-0000 ----
  document.getElementById('telefone-input').addEventListener('input', (e) => {
    let v = e.target.value.replace(/\D/g, '').slice(0, 11);
    if (v.length > 10) {
      v = v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
    } else if (v.length > 5) {
      v = v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
    } else if (v.length > 2) {
      v = v.replace(/(\d{2})(\d{0,5})/, '($1) $2');
    } else if (v.length > 0) {
      v = v.replace(/(\d{0,2})/, '($1');
    }
    e.target.value = v.replace(/-$/, '').replace(/\)\s$/, ')');
  });

  // ---- Máscara monetária (R$ 0,00) — mesma lógica do cadastro de contrato ----
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
  function aplicarMascaraMoedaValor(valorCru) {
    const num = Number(valorCru).toFixed(2);
    return 'R$ ' + num.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d)(?=,))/g, '.');
  }
  document.querySelectorAll('.money-input').forEach(el => {
    el.addEventListener('input', () => aplicarMascaraMoeda(el));
  });

  const params = new URLSearchParams(window.location.search);
  const prospectId = params.get('id');
  const form = document.getElementById('prospect-form');
  const btn = document.getElementById('submit-btn');
  const msg = document.getElementById('form-msg');

  if (prospectId) {
    document.getElementById('eyebrow').textContent = 'editar prospect';
    document.getElementById('titulo').innerHTML = 'Atualiza o <em>andamento</em> da conversa.';
    document.getElementById('breadcrumb-atual').textContent = 'Editar prospect';
    document.title = 'CRM Newsiga — Editar Prospect';
    document.getElementById('excluir-btn').style.display = 'inline';

    fetch(`buscar-prospect.php?id=${prospectId}`)
      .then(r => r.json())
      .then(data => {
        if (!data.sucesso) throw new Error(data.erro || 'Prospect não encontrado.');
        const p = data.prospect;
        document.getElementById('prospect-id').value = p.id;
        form.nome.value = p.nome || '';
        form.contato.value = p.contato || '';
        form.telefone.value = p.telefone || '';
        form.email.value = p.email || '';
        form.descricao.value = p.descricao || '';
        form.estagio.value = p.estagio || 'contato_inicial';
        form.valor_estimado.value = p.valor_estimado ? aplicarMascaraMoedaValor(p.valor_estimado) : '';
        form.observacoes.value = p.observacoes || '';
        form.proximo_contato.value = p.proximo_contato || '';
        form.prioritario.checked = String(p.prioritario) === '1';
      })
      .catch((err) => {
        msg.className = 'form-msg error';
        msg.textContent = 'Falha ao carregar este prospect.';
        console.error(err);
      });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    btn.disabled = true;
    btn.textContent = 'Salvando...';
    msg.className = 'form-msg';

    try {
      const camposMoeda = Array.from(document.querySelectorAll('.money-input'));
      const valoresOriginais = camposMoeda.map(el => el.value);
      camposMoeda.forEach(el => { el.value = moedaParaDecimal(el.value); });

      const formData = new FormData(form);

      camposMoeda.forEach((el, i) => { el.value = valoresOriginais[i]; });

      const endpoint = prospectId ? 'atualizar-prospect.php' : 'salvar-prospect.php';
      const resp = await fetch(endpoint, { method: 'POST', body: formData });
      const data = await resp.json();

      if (!resp.ok) {
        msg.className = 'form-msg error';
        msg.textContent = (data.detalhes ? data.detalhes.join(' ') : data.erro) || 'Erro ao salvar.';
      } else {
        window.location.href = 'crm-newsiga-prospects.php';
      }
    } catch (err) {
      msg.className = 'form-msg error';
      msg.textContent = 'Falha de conexão com o servidor.';
    } finally {
      btn.disabled = false;
      btn.textContent = 'Salvar prospect ↗';
    }
  });

  document.getElementById('excluir-btn').addEventListener('click', async () => {
    if (!prospectId) return;
    const confirmou = confirm('Excluir este prospect de vez? Essa ação não pode ser desfeita.');
    if (!confirmou) return;

    try {
      const fd = new FormData();
      fd.append('id', prospectId);
      const resp = await fetch('excluir-prospect.php', { method: 'POST', body: fd });
      const data = await resp.json();
      if (!resp.ok) {
        alert(data.erro || 'Erro ao excluir.');
        return;
      }
      window.location.href = 'crm-newsiga-prospects.php';
    } catch (err) {
      alert('Falha de conexão ao excluir.');
    }
  });
</script>
</body>
</html>
