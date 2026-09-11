<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Novo Cliente</title>
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
  .breadcrumb b{color:var(--forest); font-weight:600;}
  .page-head{padding:44px 0 8px;}
  .eyebrow{font-family:var(--font-ui); font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:14px;}
  h1{font-family:var(--font-display); font-weight:800; font-size:32px; letter-spacing:-.01em; line-height:1.2;}
  h1 em{font-family:var(--font-italic); font-style:italic; font-weight:400;}
  .page-sub{color:var(--muted); font-size:15px; margin-top:14px;}
  .field{margin:24px 0 18px;}
  .field label{display:block; font-family:var(--font-ui); font-size:13px; font-weight:600; margin-bottom:8px;}
  .field input{width:100%; height:46px; border-radius:8px; border:1px solid var(--border); background:var(--white); padding:0 14px; font-size:14.5px; font-family:var(--font-ui); color:var(--forest);}
  .field input:focus{outline:none; border-color:var(--forest);}
  .field .hint{font-size:12.5px; color:var(--muted); margin-top:6px;}
  .actions{display:flex; gap:12px; margin-top:20px; margin-bottom:60px;}
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
    <div class="breadcrumb" style="display:flex; align-items:center; gap:24px;">
      <a href="crm-newsiga-painel.php" style="color:var(--muted); text-decoration:none; font-weight:600;">Painel</a>
      <a href="crm-newsiga-contratos.php" style="color:var(--muted); text-decoration:none; font-weight:600;">Contratos</a>
      <a href="crm-newsiga-prospects.php" style="color:var(--muted); text-decoration:none; font-weight:600;">Prospects</a>
      <span><a href="crm-newsiga-clientes.php" style="color:var(--muted); text-decoration:none; font-weight:600;">Clientes</a> / <b>Novo cliente</b></span>
    </div>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div class="eyebrow" id="eyebrow">cadastro de cliente</div>
    <h1 id="titulo">Uma empresa nova <em>na base</em>.</h1>
    <p class="page-sub" id="page-sub">Depois de cadastrado aqui, o cliente já aparece disponível pra selecionar no formulário de novo contrato.</p>
  </div>

  <form id="cliente-form">
    <input type="hidden" name="id" id="cliente-id">
    <div class="field">
      <label>Razão social / Nome</label>
      <input type="text" name="nome" required placeholder="ex: Fortequip Ltda">
    </div>
    <div class="field">
      <label>CNPJ (ou CPF)</label>
      <input type="text" name="cnpj" id="cnpj-input" placeholder="00.000.000/0001-00" maxlength="18">
      <div class="hint">Opcional aqui, mas necessário mais à frente pra criar o cadastro no ASAAS.</div>
    </div>
    <div class="field">
      <label>E-mail de cobrança</label>
      <input type="email" name="email" placeholder="financeiro@empresa.com.br">
    </div>

    <div class="actions">
      <button type="submit" class="btn-primary" id="submit-btn">Salvar cliente ↗</button>
      <a class="btn-secondary" href="crm-newsiga-cadastro-contrato.php">Ir pro cadastro de contrato</a>
    </div>
    <div class="form-msg" id="form-msg"></div>
  </form>
</div>

<script>
  const params = new URLSearchParams(window.location.search);
  const clienteId = params.get('id');

  // Máscara de CNPJ (00.000.000/0000-00) enquanto digita — também aceita
  // CPF (11 dígitos), aplicando a máscara curta nesse caso.
  function aplicarMascaraCnpj(valor) {
    let v = valor.replace(/\D/g, '').slice(0, 14);
    if (v.length <= 11) {
      v = v.replace(/(\d{3})(\d)/, '$1.$2')
           .replace(/(\d{3})(\d)/, '$1.$2')
           .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    } else {
      v = v.replace(/(\d{2})(\d)/, '$1.$2')
           .replace(/(\d{3})(\d)/, '$1.$2')
           .replace(/(\d{3})(\d)/, '$1/$2')
           .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    }
    return v;
  }

  document.getElementById('cnpj-input').addEventListener('input', (e) => {
    e.target.value = aplicarMascaraCnpj(e.target.value);
  });

  if (clienteId) {
    document.getElementById('eyebrow').textContent = 'editar cliente';
    document.getElementById('titulo').innerHTML = 'Ajusta os dados <em>desse cliente</em>.';
    document.getElementById('page-sub').textContent = 'Alterações aqui não mexem em contratos nem no cadastro do cliente no ASAAS — se o CNPJ mudou de verdade, atualiza lá também.';
    document.getElementById('submit-btn').textContent = 'Salvar alterações ↗';
    document.title = 'CRM Newsiga — Editar Cliente';

    fetch(`buscar-cliente.php?id=${clienteId}`)
      .then(r => r.json())
      .then(data => {
        if (!data.sucesso) throw new Error(data.erro || 'Cliente não encontrado.');
        const c = data.cliente;
        document.getElementById('cliente-id').value = c.id;
        document.querySelector('[name="nome"]').value = c.nome || '';
        document.getElementById('cnpj-input').value = c.cnpj ? aplicarMascaraCnpj(c.cnpj) : '';
        document.querySelector('[name="email"]').value = c.email || '';
      })
      .catch((err) => {
        const msg = document.getElementById('form-msg');
        msg.className = 'form-msg error';
        msg.textContent = 'Falha ao carregar este cliente.';
        console.error(err);
      });
  }
</script>

<script>
  document.getElementById('cliente-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    const msg = document.getElementById('form-msg');
    btn.disabled = true;
    btn.textContent = 'Salvando...';
    msg.className = 'form-msg';

    try {
      const formData = new FormData(e.target);
      const endpoint = clienteId ? 'atualizar-cliente.php' : 'salvar-cliente.php';
      const resp = await fetch(endpoint, { method: 'POST', body: formData });
      const data = await resp.json();

      if (!resp.ok) {
        msg.className = 'form-msg error';
        msg.textContent = (data.detalhes ? data.detalhes.join(' ') : data.erro) || 'Erro ao salvar.';
      } else {
        msg.className = 'form-msg ok';
        msg.innerHTML = `Cliente "${data.nome}" salvo (#${data.cliente_id}). <a href="crm-newsiga-cliente-detalhe.php?id=${data.cliente_id}" style="color:#2f5c3f; font-weight:700; text-decoration:underline;">Ver detalhes →</a>`;
        if (!clienteId) e.target.reset(); // na edição, mantém os campos preenchidos
      }
    } catch (err) {
      msg.className = 'form-msg error';
      msg.textContent = 'Falha de conexão com o servidor.';
    } finally {
      btn.disabled = false;
      btn.textContent = clienteId ? 'Salvar alterações ↗' : 'Salvar cliente ↗';
    }
  });
</script>
</body>
</html>
