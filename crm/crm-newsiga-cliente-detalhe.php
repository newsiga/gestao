<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Detalhe do Cliente</title>
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

  .breadcrumb{font-size:13px; color:var(--muted); margin:28px 0 8px;}
  .breadcrumb a{color:var(--muted); text-decoration:none; font-weight:600;}
  .breadcrumb a:hover{color:var(--forest);}

  .page-head{padding:8px 0 28px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px;}
  .eyebrow{font-family:var(--font-ui); font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:14px;}
  h1{font-family:var(--font-display); font-weight:800; font-size:32px; letter-spacing:-.01em;}
  h1 em{font-family:var(--font-italic); font-style:italic; font-weight:400;}

  .info-card{background:var(--white); border:1px solid var(--border); border-radius:12px; padding:24px 26px; margin-bottom:28px; display:flex; gap:36px; flex-wrap:wrap;}
  .info-item small{display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-bottom:6px;}
  .info-item strong{font-family:var(--font-display); font-size:15px; font-weight:700;}
  .asaas-tag{font-size:11.5px; font-weight:700; color:#2f5c3f; background:#dbe9d8; padding:4px 10px; border-radius:20px; margin-left:8px; vertical-align:middle;}

  .section-head{display:flex; justify-content:space-between; align-items:center; margin:36px 0 16px;}
  .section-head h2{font-family:var(--font-display); font-size:18px; font-weight:800;}

  .contracts-grid{display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:14px; margin-bottom:60px;}
  .contract-card{background:var(--white); border:1px solid var(--border); border-radius:12px; padding:20px 22px; text-decoration:none; color:var(--forest); display:block; transition:border-color .12s;}
  .contract-card:hover{border-color:var(--forest);}
  .contract-card .top-row{display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:10px;}
  .contract-card .descricao{font-family:var(--font-display); font-weight:700; font-size:14.5px; line-height:1.35;}
  .tipo-badge{font-family:var(--font-ui); font-size:11px; font-weight:600; padding:4px 10px; border-radius:6px; background:var(--card); color:var(--forest); white-space:nowrap; display:inline-block; margin-top:8px;}
  .condicoes{color:var(--muted); font-size:12.5px; margin-top:10px;}

  .status-pill{font-family:var(--font-ui); font-size:11px; font-weight:700; padding:5px 12px; border-radius:20px; white-space:nowrap; display:inline-block; flex-shrink:0;}
  .status-pill.rascunho{background:#f3e6c9; color:#8a6414;}
  .status-pill.aprovado{background:#dbe4f3; color:#2f4d7c;}
  .status-pill.ativo{background:#dbe9d8; color:#2f5c3f;}
  .status-pill.concluido{background:#d6e8f5; color:#1f5c85;}
  .status-pill.encerrado{background:#e2e0d8; color:var(--muted);}

  .empty-state{padding:50px 22px; text-align:center; color:var(--muted); font-size:14px; background:var(--white); border:1px solid var(--border); border-radius:12px; margin-bottom:60px;}
  .empty-state a{color:var(--forest); font-weight:700;}

  @media (max-width:860px){
    .nav-links{display:none;}
    .info-card{flex-direction:column; gap:16px;}
    .contracts-grid{grid-template-columns:1fr;}
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
    <a class="btn-nav" href="crm-newsiga-cadastro-contrato.php" id="btn-novo-contrato">+ Novo contrato</a>
  </div>
</nav>

<div class="wrap">
  <div class="breadcrumb"><a href="crm-newsiga-clientes.php">← Clientes</a></div>

  <div class="page-head">
    <div>
      <div class="eyebrow">detalhe do cliente</div>
      <h1 id="titulo-cliente">Carregando...</h1>
    </div>
    <a id="link-editar-cliente" href="#" style="color:var(--forest); font-weight:600; font-size:13.5px; text-decoration:none; display:none;">editar cliente →</a>
  </div>

  <div class="info-card" id="info-card" style="display:none;">
    <div class="info-item">
      <small>CNPJ</small>
      <strong id="info-cnpj">—</strong>
    </div>
    <div class="info-item">
      <small>E-mail de cobrança</small>
      <strong id="info-email">—</strong>
    </div>
    <div class="info-item">
      <small>ASAAS</small>
      <strong id="info-asaas">—</strong>
    </div>
  </div>

  <div class="section-head">
    <h2>Contratos</h2>
  </div>

  <div id="contratos-area">
    <div class="empty-state">Carregando contratos...</div>
  </div>
</div>

<script>
  const params = new URLSearchParams(window.location.search);
  const clienteId = params.get('id');

  const tipoLabels = {
    mensalidade_fixa: 'valor fixo',
    hora_aberta: 'hora aberta',
    banco_horas_minimo: 'banco c/ mínimo',
    banco_horas_consumo: 'banco s/ mínimo',
    projeto_parcelado: 'projeto parcelado',
  };
  const statusLabels = { rascunho: 'Rascunho', aprovado: 'Aprovado', ativo: 'Ativo', concluido: 'Concluído', encerrado: 'Encerrado' };

  const fmt = (v) => v === null || v === undefined ? '—' : 'R$ ' + Number(v).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});

  function condicoesDe(c) {
    if (c.tipo === 'banco_horas_minimo') {
      return c.valor_hora ? `mín. ${fmt(c.valor)} · excedente ${fmt(c.valor_hora)}/h` : `mín. ${fmt(c.valor)} · sem excedente`;
    }
    if (c.tipo === 'banco_horas_consumo') {
      return `${c.horas_banco}h no banco · ${fmt(c.valor_hora)}/h · excedente ${fmt(c.valor_hora_excedente)}/h`;
    }
    if (c.tipo === 'hora_aberta') return `${fmt(c.valor_hora)}/h`;
    if (c.tipo === 'mensalidade_fixa') return fmt(c.valor);
    return c.valor_parcelas_total ? `${fmt(c.valor_parcelas_total)} no total` : 'ver parcelas';
  }

  if (!clienteId) {
    document.getElementById('titulo-cliente').textContent = 'Cliente não informado';
    document.getElementById('contratos-area').innerHTML = '<div class="empty-state">Nenhum id de cliente foi passado na URL.</div>';
  } else {
    document.getElementById('btn-novo-contrato').href = `crm-newsiga-cadastro-contrato.php?cliente_id=${clienteId}`;

    fetch(`buscar-cliente.php?id=${clienteId}`)
      .then(r => r.json())
      .then(data => {
        if (!data.sucesso) throw new Error(data.erro || 'Cliente não encontrado.');
        const cliente = data.cliente;
        document.getElementById('titulo-cliente').innerHTML = `${cliente.nome}`;
        document.title = `CRM Newsiga — ${cliente.nome}`;

        document.getElementById('info-cnpj').textContent = cliente.cnpj || '—';
        document.getElementById('info-email').textContent = cliente.email || '—';
        document.getElementById('info-asaas').innerHTML = cliente.asaas_customer_id
          ? `<span class="asaas-tag">cadastrado</span>`
          : '<span style="color:var(--muted); font-weight:400;">ainda não</span>';
        document.getElementById('info-card').style.display = 'flex';

        const linkEditar = document.getElementById('link-editar-cliente');
        linkEditar.href = `crm-newsiga-cadastro-cliente.php?id=${clienteId}`;
        linkEditar.style.display = 'inline';
      })
      .catch((err) => {
        document.getElementById('titulo-cliente').textContent = 'Erro ao carregar cliente';
        console.error(err);
      });

    fetch('listar-contratos.php')
      .then(r => r.json())
      .then(data => {
        const area = document.getElementById('contratos-area');
        if (!data.sucesso) throw new Error(data.erro || 'Falha ao listar contratos.');

        const contratosDoCliente = (data.contratos || []).filter(c => String(c.cliente_id) === String(clienteId));

        if (contratosDoCliente.length === 0) {
          area.innerHTML = `<div class="empty-state">Nenhum contrato cadastrado ainda para este cliente. <a href="crm-newsiga-cadastro-contrato.php?cliente_id=${clienteId}">Criar o primeiro →</a></div>`;
          return;
        }

        area.innerHTML = `<div class="contracts-grid">${contratosDoCliente.map(c => `
          <a class="contract-card" href="crm-newsiga-editar-contrato.php?id=${c.id}">
            <div class="top-row">
              <div class="descricao">${c.descricao || '(sem descrição)'}</div>
              <span class="status-pill ${c.status}">${statusLabels[c.status] || c.status}</span>
            </div>
            <span class="tipo-badge">${tipoLabels[c.tipo] || c.tipo}</span>
            <div class="condicoes">${condicoesDe(c)}</div>
          </a>
        `).join('')}</div>`;
      })
      .catch(() => {
        document.getElementById('contratos-area').innerHTML = '<div class="empty-state" style="color:var(--red);">Falha ao carregar contratos do servidor.</div>';
      });
  }
</script>
</body>
</html>
