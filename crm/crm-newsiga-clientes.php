<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Clientes</title>
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
  .nav-links{display:flex; gap:26px; font-size:13.5px; color:var(--muted); font-weight:600;}
  .nav-links a{color:var(--muted); text-decoration:none;}
  .nav-links a.active{color:var(--forest);}
  .btn-nav{font-family:var(--font-ui); font-size:13px; font-weight:700; background:var(--forest); color:var(--white); padding:10px 18px; border-radius:8px; text-decoration:none;}
  .page-head{padding:44px 0 8px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px;}
  .eyebrow{font-family:var(--font-ui); font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:14px;}
  h1{font-family:var(--font-display); font-weight:800; font-size:32px; letter-spacing:-.01em;}
  h1 em{font-family:var(--font-italic); font-style:italic; font-weight:400;}

  .search-row{margin:28px 0 20px;}
  .search-input{width:100%; max-width:360px; height:44px; border-radius:8px; border:1px solid var(--border); background:var(--white); padding:0 14px; font-size:14px; font-family:var(--font-ui); color:var(--forest);}
  .search-input:focus{outline:none; border-color:var(--forest);}

  .panel{background:var(--white); border:1px solid var(--border); border-radius:12px; overflow:hidden; margin-bottom:60px;}
  table{width:100%; border-collapse:collapse; font-size:13.5px;}
  th{text-align:left; font-family:var(--font-ui); font-size:11.5px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; font-weight:700; padding:14px 22px; border-bottom:1px solid var(--border);}
  td{padding:16px 22px; border-bottom:1px solid var(--border); vertical-align:middle;}
  tr:last-child td{border-bottom:none;}
  tr.client-row{cursor:pointer;}
  tr.client-row:hover{background:#faf9f6;}
  td.name{font-family:var(--font-display); font-weight:700;}
  .cnpj{color:var(--muted); font-size:12.5px;}
  .badge{font-family:var(--font-ui); font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px; background:var(--card); color:var(--forest); white-space:nowrap; display:inline-block;}
  .badge.zero{color:var(--muted);}
  .asaas-pill{font-size:11px; font-weight:600; color:#2f5c3f; margin-left:8px;}

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
      <a href="crm-newsiga-clientes.php" class="active">Clientes</a>
      <a href="crm-newsiga-contratos.php">Contratos</a>
      <a href="crm-newsiga-prospects.php">Prospects</a>
    </div>
    <a class="btn-nav" href="crm-newsiga-cadastro-cliente.php">+ Novo cliente</a>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div>
      <div class="eyebrow">todos os clientes</div>
      <h1>Cada cliente, com seus <em>contratos</em> à mão.</h1>
    </div>
  </div>

  <div class="search-row">
    <input type="text" class="search-input" id="busca" placeholder="Buscar por nome ou CNPJ...">
  </div>

  <div class="panel">
    <table>
      <thead>
        <tr><th>Cliente</th><th>Contratos ativos</th><th></th></tr>
      </thead>
      <tbody id="clientes-tbody">
        <tr><td colspan="3" style="color:var(--muted); font-style:italic; padding:24px;">Carregando clientes...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  let todosClientes = [];

  function renderizar(filtro) {
    const tbody = document.getElementById('clientes-tbody');
    const termo = (filtro || '').trim().toLowerCase();
    const lista = termo
      ? todosClientes.filter(c =>
          (c.nome || '').toLowerCase().includes(termo) ||
          (c.cnpj || '').toLowerCase().includes(termo))
      : todosClientes;

    if (lista.length === 0) {
      tbody.innerHTML = '<tr><td colspan="3"><div class="empty-state">Nenhum cliente' + (termo ? ' encontrado.' : ' cadastrado ainda.') + '</div></td></tr>';
      return;
    }

    tbody.innerHTML = lista.map(c => {
      const qtd = Number(c.contratos_ativos || 0);
      const badgeClasse = qtd === 0 ? 'badge zero' : 'badge';
      const badgeTexto = qtd === 0 ? 'nenhum ativo' : `${qtd} contrato${qtd === 1 ? '' : 's'}`;
      const asaasPill = c.asaas_customer_id ? '<span class="asaas-pill">· no ASAAS</span>' : '';
      return `
        <tr class="client-row" data-id="${c.id}">
          <td class="name">${c.nome}${c.cnpj ? `<div class="cnpj">${c.cnpj}</div>` : ''}</td>
          <td><span class="${badgeClasse}">${badgeTexto}</span>${asaasPill}</td>
          <td style="color:var(--forest); font-weight:600; font-size:13px;">ver detalhes →</td>
        </tr>`;
    }).join('');

    document.querySelectorAll('.client-row').forEach(row => {
      row.addEventListener('click', () => {
        window.location.href = `crm-newsiga-cliente-detalhe.php?id=${row.dataset.id}`;
      });
    });
  }

  document.getElementById('busca').addEventListener('input', (e) => renderizar(e.target.value));

  fetch('listar-clientes.php')
    .then(r => r.json())
    .then(data => {
      todosClientes = (data.sucesso && data.clientes) ? data.clientes : [];
      renderizar('');
    })
    .catch(() => {
      document.getElementById('clientes-tbody').innerHTML =
        '<tr><td colspan="3" style="color:var(--red); padding:24px;">Falha ao carregar clientes do servidor.</td></tr>';
    });
</script>
</body>
</html>
