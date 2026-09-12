<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Prospects</title>
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

  .filters{display:flex; gap:8px; margin:28px 0 20px; flex-wrap:wrap;}
  .filter-btn{font-family:var(--font-ui); font-size:13px; font-weight:600; padding:9px 16px; border-radius:20px; border:1px solid var(--border); background:var(--white); color:var(--muted); cursor:pointer;}
  .filter-btn.active{background:var(--forest); color:var(--white); border-color:var(--forest);}

  .panel{background:var(--white); border:1px solid var(--border); border-radius:12px; overflow:hidden; margin-bottom:60px;}
  table{width:100%; border-collapse:collapse; font-size:13.5px;}
  th{text-align:left; font-family:var(--font-ui); font-size:11.5px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; font-weight:700; padding:14px 22px; border-bottom:1px solid var(--border);}
  td{padding:16px 22px; border-bottom:1px solid var(--border); vertical-align:middle;}
  tr:last-child td{border-bottom:none;}
  td.name{font-family:var(--font-display); font-weight:700;}
  td.name .prio{color:#a06a1f; font-size:11px; font-weight:700; margin-left:8px;}
  .contato-info{color:var(--muted); font-size:12.5px;}

  .status-pill{font-family:var(--font-ui); font-size:11px; font-weight:700; padding:5px 12px; border-radius:20px; white-space:nowrap; display:inline-block;}
  .status-pill.contato_inicial{background:#e2e0d8; color:var(--muted);}
  .status-pill.em_negociacao{background:#f3e6c9; color:#8a6414;}
  .status-pill.proposta_enviada{background:#dbe4f3; color:#2f4d7c;}
  .status-pill.ganho{background:#dbe9d8; color:#2f5c3f;}
  .status-pill.perdido{background:#f1d9d4; color:var(--red);}

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
      <a href="crm-newsiga-prospects.php" class="active">Prospects</a>
    </div>
    <a class="btn-nav" href="crm-newsiga-cadastro-prospect.php">+ Novo prospect</a>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div>
      <div class="eyebrow">pipeline comercial</div>
      <h1>Negócios em <em>andamento</em>, sem perder de vista.</h1>
    </div>
  </div>

  <div class="filters" id="filters">
    <button class="filter-btn active" data-estagio="">Todos</button>
    <button class="filter-btn" data-estagio="contato_inicial">Contato inicial</button>
    <button class="filter-btn" data-estagio="em_negociacao">Em negociação</button>
    <button class="filter-btn" data-estagio="proposta_enviada">Proposta enviada</button>
    <button class="filter-btn" data-estagio="ganho">Ganho</button>
    <button class="filter-btn" data-estagio="perdido">Perdido</button>
  </div>

  <div class="panel">
    <table>
      <thead>
        <tr><th>Empresa</th><th>Contato</th><th>Negociando</th><th>Estágio</th><th>Próximo contato</th><th></th></tr>
      </thead>
      <tbody id="prospects-tbody">
        <tr><td colspan="6" style="color:var(--muted); font-style:italic; padding:24px;">Carregando prospects...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  const estagioLabels = {
    contato_inicial: 'Contato inicial',
    em_negociacao: 'Em negociação',
    proposta_enviada: 'Proposta enviada',
    ganho: 'Ganho',
    perdido: 'Perdido',
  };
  const fmt = (v) => v === null || v === undefined ? '' : 'R$ ' + Number(v).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});

  let todosProspects = [];
  let filtroAtivo = '';

  function renderizar() {
    const tbody = document.getElementById('prospects-tbody');
    const lista = filtroAtivo ? todosProspects.filter(p => p.estagio === filtroAtivo) : todosProspects;

    if (lista.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state">Nenhum prospect' + (filtroAtivo ? ' nesse estágio' : ' cadastrado ainda') + '.</div></td></tr>';
      return;
    }

    const hoje = new Date().toISOString().slice(0, 10);

    tbody.innerHTML = lista.map(p => {
      const contatoLinhas = [p.contato, p.email, p.telefone].filter(Boolean).join(' · ');
      const valorTexto = p.valor_estimado ? ` <span style="color:var(--muted); font-weight:400;">(${fmt(p.valor_estimado)})</span>` : '';
      let proximoContatoTexto = '—';
      if (p.proximo_contato) {
        const [ano, mes, dia] = p.proximo_contato.split('-');
        const vencido = p.proximo_contato < hoje && p.estagio !== 'ganho' && p.estagio !== 'perdido';
        proximoContatoTexto = `<span style="${vencido ? 'color:var(--red); font-weight:700;' : ''}">${dia}/${mes}/${ano}</span>`;
      }
      return `
        <tr>
          <td class="name">${p.nome}${p.prioritario == 1 ? '<span class="prio">★ prioritário</span>' : ''}</td>
          <td class="contato-info">${contatoLinhas || '—'}</td>
          <td>${p.descricao || '—'}${valorTexto}</td>
          <td><span class="status-pill ${p.estagio}">${estagioLabels[p.estagio] || p.estagio}</span></td>
          <td>${proximoContatoTexto}</td>
          <td><a href="crm-newsiga-cadastro-prospect.php?id=${p.id}" style="color:var(--forest); font-weight:600; font-size:13px; text-decoration:none;">editar</a></td>
        </tr>`;
    }).join('');
  }

  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      filtroAtivo = btn.dataset.estagio;
      renderizar();
    });
  });

  fetch('listar-prospects.php')
    .then(r => r.json())
    .then(data => {
      todosProspects = (data.sucesso && data.prospects) ? data.prospects : [];
      renderizar();
    })
    .catch(() => {
      document.getElementById('prospects-tbody').innerHTML =
        '<tr><td colspan="6" style="color:var(--red); padding:24px;">Falha ao carregar prospects do servidor.</td></tr>';
    });
</script>
</body>
</html>
