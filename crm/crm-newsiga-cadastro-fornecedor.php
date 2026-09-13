<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Fornecedor</title>
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
  .wrap{max-width:720px; margin:0 auto; padding:0 32px;}
  nav{border-bottom:1px solid var(--border); padding:22px 0;}
  nav .wrap{display:flex; align-items:center; justify-content:space-between; max-width:1160px;}
  .logo{font-family:var(--font-display); font-weight:800; font-size:19px; color:var(--forest); text-decoration:none;}
  .logo span{color:var(--muted); font-weight:600; font-size:13px; margin-left:8px;}
  .breadcrumb{font-size:13px; color:var(--muted);}
  .page-head{padding:44px 0 8px;}
  .eyebrow{font-family:var(--font-ui); font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:14px;}
  h1{font-family:var(--font-display); font-weight:800; font-size:32px; letter-spacing:-.01em; line-height:1.2;}
  h1 em{font-family:var(--font-italic); font-style:italic; font-weight:400;}
  .section{margin:36px 0;}
  .field{margin-bottom:18px;}
  .field label{display:block; font-family:var(--font-ui); font-size:13px; font-weight:600; color:var(--forest); margin-bottom:8px;}
  .field .hint{font-size:12.5px; color:var(--muted); margin-top:6px;}
  .field input, .field select{width:100%; height:46px; border-radius:8px; border:1px solid var(--border); background:var(--white); padding:0 14px; font-size:14.5px; font-family:var(--font-ui); color:var(--forest);}
  .field input:focus, .field select:focus{outline:none; border-color:var(--forest);}
  .row2{display:grid; grid-template-columns:1fr 1fr; gap:14px;}
  .type-grid{display:grid; grid-template-columns:1fr 1fr; gap:12px;}
  .type-card{background:var(--card); border:2px solid transparent; border-radius:10px; padding:18px; cursor:pointer;}
  .type-card:hover{background:#dedcd4;}
  .type-card.selected{background:var(--forest); border-color:var(--forest);}
  .type-card h4{font-family:var(--font-display); font-weight:700; font-size:15px; margin-bottom:6px;}
  .type-card.selected h4{color:var(--white);}
  .type-card p{font-size:13px; color:var(--muted);}
  .type-card.selected p{color:#b8c4be;}
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
    <div class="breadcrumb"><a href="crm-newsiga-fornecedores.php" style="color:var(--muted); text-decoration:none; font-weight:600;">Fornecedores</a> / <b style="color:var(--forest);" id="breadcrumb-atual">Novo</b></div>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div class="eyebrow" id="eyebrow">cadastro de fornecedor</div>
    <h1 id="titulo">Quem a Newsiga <em>paga</em> — cadastro novo.</h1>
  </div>

  <form id="fornecedor-form">
    <input type="hidden" name="id" id="fornecedor-id">

    <div class="section">
      <div class="field"><label>Nome do fornecedor</label><input type="text" name="nome" id="nome" required placeholder="ex: João Silva (consultor)"></div>
    </div>

    <div class="section">
      <label style="display:block; font-family:var(--font-ui); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); margin-bottom:14px;">Tipo de fornecedor</label>
      <input type="hidden" name="tipo" id="tipo-input" value="operacional">
      <div class="type-grid">
        <div class="type-card selected" data-tipo="operacional"><h4>Operacional</h4><p>Consultor/técnico que fecha chamados — pode ter vínculo com o Movidesk.</p></div>
        <div class="type-card" data-tipo="fixo"><h4>Fixo/recorrente</h4><p>Contabilidade, impostos, assinaturas — valor fixo, sem lógica de consumo.</p></div>
      </div>
    </div>

    <div class="section">
      <div class="field">
        <label>Categoria <span style="color:var(--muted); font-weight:400;">— opcional, pra agrupar nos relatórios de custo</span></label>
        <input type="text" name="categoria" id="categoria" list="categorias-sugeridas" placeholder="ex: Funcionário, Terceirizado, Contabilidade, Imposto, Software...">
        <datalist id="categorias-sugeridas">
          <option value="Funcionário">
          <option value="Terceirizado">
          <option value="Contabilidade">
          <option value="Imposto">
          <option value="Software">
        </datalist>
        <div class="hint">Pode digitar uma categoria nova a qualquer momento — a lista acima é só sugestão, não trava em nenhuma opção fixa.</div>
      </div>
      <div class="field">
        <label>Nome do técnico no Movidesk <span style="color:var(--muted); font-weight:400;">— opcional</span></label>
        <input type="text" name="movidesk_technician_name" id="movidesk_technician_name" placeholder="ex: João Silva">
        <div class="hint">Só preencha se este fornecedor corresponder a um técnico cadastrado no Movidesk — necessário pro cálculo automático de custo por chamado fechado (fase futura). Deixe em branco se não houver correspondência.</div>
      </div>
      <div class="field"><label>Forma de pagamento <span style="color:var(--muted); font-weight:400;">— opcional</span></label><input type="text" name="forma_pagamento" id="forma_pagamento" placeholder="ex: PIX, transferência, boleto"></div>
    </div>

    <div class="section" id="status-section" style="display:none;">
      <div class="field">
        <label>Status</label>
        <select name="status" id="status">
          <option value="ativo">Ativo</option>
          <option value="inativo">Inativo</option>
        </select>
      </div>
    </div>

    <div class="actions">
      <button type="submit" class="btn-primary" id="submit-btn">Salvar fornecedor</button>
      <a href="crm-newsiga-fornecedores.php" class="btn-secondary">Cancelar</a>
    </div>
    <div class="form-msg" id="form-msg"></div>
  </form>
</div>

<script>
  document.querySelectorAll('.type-card').forEach(card => {
    card.addEventListener('click', () => {
      document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      document.getElementById('tipo-input').value = card.dataset.tipo;
    });
  });

  function selecionarTipo(tipo) {
    document.getElementById('tipo-input').value = tipo;
    document.querySelectorAll('.type-card').forEach(c => c.classList.toggle('selected', c.dataset.tipo === tipo));
  }

  const fornecedorId = new URLSearchParams(window.location.search).get('id');
  const ehEdicao = !!fornecedorId;

  if (ehEdicao) {
    document.getElementById('breadcrumb-atual').textContent = 'Editar';
    document.getElementById('eyebrow').textContent = 'editar fornecedor';
    document.getElementById('titulo').innerHTML = 'Atualizando um fornecedor <em>já cadastrado</em>.';
    document.getElementById('submit-btn').textContent = 'Salvar alterações';
    document.getElementById('status-section').style.display = 'block';

    fetch(`buscar-fornecedor.php?id=${fornecedorId}`)
      .then(r => r.json())
      .then(data => {
        if (!data.sucesso) { alert(data.erro || 'Fornecedor não encontrado.'); return; }
        const f = data.fornecedor;
        document.getElementById('fornecedor-id').value = f.id;
        document.getElementById('nome').value = f.nome;
        document.getElementById('categoria').value = f.categoria || '';
        document.getElementById('movidesk_technician_name').value = f.movidesk_technician_name || '';
        document.getElementById('forma_pagamento').value = f.forma_pagamento || '';
        document.getElementById('status').value = f.status;
        selecionarTipo(f.tipo);
      })
      .catch(() => alert('Falha ao carregar dados do fornecedor.'));
  }

  document.getElementById('fornecedor-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    const msg = document.getElementById('form-msg');
    btn.disabled = true;
    btn.textContent = 'Salvando...';
    msg.className = 'form-msg';

    try {
      const formData = new FormData(e.target);
      const endpoint = ehEdicao ? 'atualizar-fornecedor.php' : 'salvar-fornecedor.php';
      const resp = await fetch(endpoint, { method: 'POST', body: formData });
      const data = await resp.json();

      if (!resp.ok) {
        msg.className = 'form-msg error';
        msg.textContent = (data.detalhes ? data.detalhes.join(' ') : data.erro) || 'Erro ao salvar.';
      } else {
        msg.className = 'form-msg ok';
        // Ao CRIAR um fornecedor novo, o próximo passo natural é cadastrar
        // o primeiro contrato dele (é lá que entram os valores — um
        // fornecedor pode ter vários contratos, um por relação/cliente).
        // Na edição, volta pra listagem normalmente.
        if (ehEdicao) {
          msg.textContent = 'Fornecedor atualizado com sucesso.';
          setTimeout(() => { window.location.href = 'crm-newsiga-fornecedores.php'; }, 900);
        } else {
          msg.textContent = 'Fornecedor salvo. Agora cadastre o primeiro contrato (é lá que entra o valor)...';
          setTimeout(() => { window.location.href = `crm-newsiga-cadastro-contrato-fornecedor.php?fornecedor_id=${data.fornecedor_id}`; }, 900);
        }
      }
    } catch (err) {
      msg.className = 'form-msg error';
      msg.textContent = 'Falha de conexão com o servidor.';
    } finally {
      btn.disabled = false;
      btn.textContent = ehEdicao ? 'Salvar alterações' : 'Salvar fornecedor';
    }
  });
</script>
</body>
</html>
