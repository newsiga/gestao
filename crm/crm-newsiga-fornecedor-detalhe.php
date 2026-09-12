<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Detalhe do Fornecedor</title>
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
  .nav-links{display:flex; gap:22px; font-size:13.5px; color:var(--muted); font-weight:600;}
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
  .head-actions{display:flex; gap:18px; align-items:center;}
  .head-actions a{color:var(--forest); font-weight:600; font-size:13.5px; text-decoration:none;}
  .head-actions .excluir{color:var(--red);}

  .info-card{background:var(--white); border:1px solid var(--border); border-radius:12px; padding:24px 26px; margin-bottom:28px; display:flex; gap:36px; flex-wrap:wrap;}
  .info-item small{display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-bottom:6px;}
  .info-item strong{font-family:var(--font-display); font-size:15px; font-weight:700;}
  .movidesk-tag{font-size:11.5px; font-weight:700; color:#2f5c3f; background:#dbe9d8; padding:4px 10px; border-radius:20px; margin-left:8px; vertical-align:middle;}

  .section-head{display:flex; justify-content:space-between; align-items:center; margin:36px 0 16px;}
  .section-head h2{font-family:var(--font-display); font-size:18px; font-weight:800;}

  .contracts-grid{display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:14px; margin-bottom:60px;}
  .contract-card{background:var(--white); border:1px solid var(--border); border-radius:12px; padding:20px 22px;}
  .contract-card .top-row{display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:10px;}
  .contract-card .descricao{font-family:var(--font-display); font-weight:700; font-size:14.5px; line-height:1.35;}
  .tipo-badge{font-family:var(--font-ui); font-size:11px; font-weight:600; padding:4px 10px; border-radius:6px; background:var(--card); color:var(--forest); white-space:nowrap; display:inline-block; margin-top:8px;}
  .condicoes{color:var(--muted); font-size:12.5px; margin-top:10px;}

  .status-pill{font-family:var(--font-ui); font-size:11px; font-weight:700; padding:5px 12px; border-radius:20px; white-space:nowrap; display:inline-block; flex-shrink:0;}
  .status-pill.rascunho{background:#f3e6c9; color:#8a6414;}
  .status-pill.ativo{background:#dbe9d8; color:#2f5c3f;}
  .status-pill.concluido{background:#d6e8f5; color:#1f5c85;}
  .status-pill.encerrado{background:#e2e0d8; color:var(--muted);}

  .contract-footer{display:flex; justify-content:space-between; align-items:center; margin-top:14px; padding-top:14px; border-top:1px solid var(--border);}
  .status-select{font-family:var(--font-ui); font-size:12.5px; font-weight:600; color:var(--forest); background:var(--white); border:1px solid var(--border); border-radius:6px; padding:6px 10px; cursor:pointer;}
  .excluir-link{color:var(--red); font-weight:600; font-size:12.5px; text-decoration:none;}
  .row-msg{font-size:11px; color:var(--muted); margin-top:6px;}
  .row-msg.error{color:var(--red);}

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
      <a href="crm-newsiga-clientes.php">Clientes</a>
      <a href="crm-newsiga-contratos.php">Contratos</a>
      <a href="crm-newsiga-fornecedores.php" class="active">Fornecedores</a>
      <a href="crm-newsiga-despesas.php">Despesas</a>
      <a href="crm-newsiga-prospects.php">Prospects</a>
    </div>
    <a class="btn-nav" id="btn-novo-contrato" href="crm-newsiga-cadastro-contrato-fornecedor.php">+ Novo contrato</a>
  </div>
</nav>

<div class="wrap">
  <div class="breadcrumb"><a href="crm-newsiga-fornecedores.php">← Fornecedores</a></div>

  <div class="page-head">
    <div>
      <div class="eyebrow">detalhe do fornecedor</div>
      <h1 id="titulo-fornecedor">Carregando...</h1>
    </div>
    <div class="head-actions" id="head-actions" style="display:none;">
      <a id="link-editar-fornecedor" href="#">editar cadastro →</a>
      <a id="link-excluir-fornecedor" href="#" class="excluir">excluir fornecedor</a>
    </div>
  </div>

  <div class="info-card" id="info-card" style="display:none;">
    <div class="info-item">
      <small>Tipo</small>
      <strong id="info-tipo">—</strong>
    </div>
    <div class="info-item">
      <small>Forma de pagamento</small>
      <strong id="info-forma-pagamento">—</strong>
    </div>
    <div class="info-item">
      <small>Movidesk</small>
      <strong id="info-movidesk">—</strong>
    </div>
    <div class="info-item">
      <small>Status</small>
      <strong id="info-status">—</strong>
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
  const fornecedorId = new URLSearchParams(window.location.search).get('id');

  const tipoLabels = { mensalidade_fixa: 'valor fixo', hora_aberta: 'hora aberta' };
  const tipoFornecedorLabels = { operacional: 'operacional', fixo: 'fixo/recorrente' };
  const statusLabels = { rascunho: 'Rascunho', ativo: 'Ativo', concluido: 'Concluído', encerrado: 'Encerrado' };
  const proximosStatus = {
    rascunho: ['rascunho', 'ativo', 'encerrado'],
    ativo: ['ativo', 'concluido', 'encerrado'],
    concluido: ['concluido', 'ativo', 'encerrado'],
    encerrado: ['encerrado'],
  };

  const fmt = (v) => v === null || v === undefined ? '—' : 'R$ ' + Number(v).toLocaleString('pt-BR', {minimumFractionDigits: 2});

  function condicoesDe(c) {
    if (c.tipo === 'hora_aberta') {
      const escopo = c.cliente_id ? `exceção: ${c.cliente_nome}` : 'regra geral';
      return `${fmt(c.valor_hora)}/h · ${escopo} · vence dia ${c.dia_vencimento}`;
    }
    if (c.tipo === 'mensalidade_fixa') return `${fmt(c.valor)}/mês · vence dia ${c.dia_vencimento}`;
    return '—';
  }

  let todosContratosDoFornecedor = [];

  function renderContratos() {
    const area = document.getElementById('contratos-area');

    if (todosContratosDoFornecedor.length === 0) {
      area.innerHTML = `<div class="empty-state">Nenhum contrato cadastrado ainda para este fornecedor. <a href="crm-newsiga-cadastro-contrato-fornecedor.php?fornecedor_id=${fornecedorId}">Criar o primeiro →</a></div>`;
      return;
    }

    area.innerHTML = `<div class="contracts-grid">${todosContratosDoFornecedor.map(c => {
      const opcoes = (proximosStatus[c.status] || [c.status]).map(s =>
        `<option value="${s}" ${s === c.status ? 'selected' : ''}>${statusLabels[s]}</option>`
      ).join('');
      const botaoExcluir = c.status === 'rascunho'
        ? `<a href="#" class="excluir-link" data-id="${c.id}">excluir</a>`
        : '<span></span>';
      return `
        <div class="contract-card" data-id="${c.id}">
          <div class="top-row">
            <div class="descricao">${c.descricao || '(sem descrição)'}</div>
            <span class="status-pill ${c.status}">${statusLabels[c.status] || c.status}</span>
          </div>
          <span class="tipo-badge">${tipoLabels[c.tipo] || c.tipo}</span>
          <div class="condicoes">${condicoesDe(c)}</div>
          <div class="contract-footer">
            <select class="status-select" data-id="${c.id}" data-atual="${c.status}">${opcoes}</select>
            <div>
              <a href="crm-newsiga-cadastro-contrato-fornecedor.php?id=${c.id}" style="color:var(--forest); font-weight:600; font-size:12.5px; text-decoration:none; margin-right:12px;">editar</a>
              ${botaoExcluir}
            </div>
          </div>
          <div class="row-msg" id="msg-${c.id}"></div>
        </div>`;
    }).join('')}</div>`;

    document.querySelectorAll('.excluir-link').forEach(link => {
      link.addEventListener('click', async (e) => {
        e.preventDefault();
        const contratoId = link.dataset.id;
        if (!confirm('Excluir de vez este contrato de fornecedor? Essa ação não pode ser desfeita.')) return;
        try {
          const fd = new FormData();
          fd.append('contrato_id', contratoId);
          const resp = await fetch('excluir-contrato-fornecedor.php', { method: 'POST', body: fd });
          const data = await resp.json();
          if (!resp.ok) { alert(data.erro || 'Erro ao excluir.'); return; }
          todosContratosDoFornecedor = todosContratosDoFornecedor.filter(c => String(c.id) !== String(contratoId));
          renderContratos();
        } catch (err) {
          alert('Falha de conexão ao excluir.');
        }
      });
    });

    document.querySelectorAll('.status-select').forEach(sel => {
      sel.addEventListener('change', async () => {
        const contratoId = sel.dataset.id;
        const novoStatus = sel.value;
        const statusAnterior = sel.dataset.atual;
        const msg = document.getElementById('msg-' + contratoId);
        sel.disabled = true;
        msg.className = 'row-msg';
        msg.textContent = 'Salvando...';

        try {
          const formData = new FormData();
          formData.append('contrato_id', contratoId);
          formData.append('status', novoStatus);
          const resp = await fetch('atualizar-status-contrato-fornecedor.php', { method: 'POST', body: formData });
          const data = await resp.json();

          if (!resp.ok) {
            msg.className = 'row-msg error';
            msg.textContent = data.erro || 'Erro ao salvar.';
            sel.value = statusAnterior;
          } else {
            const item = todosContratosDoFornecedor.find(c => String(c.id) === String(contratoId));
            if (item) item.status = novoStatus;
            renderContratos();
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

  if (!fornecedorId) {
    document.getElementById('titulo-fornecedor').textContent = 'Fornecedor não informado';
    document.getElementById('contratos-area').innerHTML = '<div class="empty-state">Nenhum id de fornecedor foi passado na URL.</div>';
  } else {
    document.getElementById('btn-novo-contrato').href = `crm-newsiga-cadastro-contrato-fornecedor.php?fornecedor_id=${fornecedorId}`;

    fetch(`buscar-fornecedor.php?id=${fornecedorId}`)
      .then(r => r.json())
      .then(data => {
        if (!data.sucesso) throw new Error(data.erro || 'Fornecedor não encontrado.');
        const f = data.fornecedor;
        document.getElementById('titulo-fornecedor').textContent = f.nome;
        document.title = `CRM Newsiga — ${f.nome}`;

        document.getElementById('info-tipo').textContent = tipoFornecedorLabels[f.tipo] || f.tipo;
        document.getElementById('info-forma-pagamento').textContent = f.forma_pagamento || '—';
        document.getElementById('info-movidesk').innerHTML = f.movidesk_technician_name
          ? `${f.movidesk_technician_name} <span class="movidesk-tag">vinculado</span>`
          : '<span style="color:var(--muted); font-weight:400;">sem vínculo</span>';
        document.getElementById('info-status').textContent = f.status;
        document.getElementById('info-card').style.display = 'flex';

        document.getElementById('link-editar-fornecedor').href = `crm-newsiga-cadastro-fornecedor.php?id=${fornecedorId}`;
        document.getElementById('link-excluir-fornecedor').addEventListener('click', async (e) => {
          e.preventDefault();
          if (!confirm(`Excluir de vez o fornecedor "${f.nome}"? Só é possível se ele não tiver nenhum contrato associado.`)) return;
          try {
            const fd = new FormData();
            fd.append('fornecedor_id', fornecedorId);
            const resp = await fetch('excluir-fornecedor.php', { method: 'POST', body: fd });
            const data2 = await resp.json();
            if (!resp.ok) { alert(data2.erro || 'Erro ao excluir.'); return; }
            window.location.href = 'crm-newsiga-fornecedores.php';
          } catch (err) {
            alert('Falha de conexão ao excluir.');
          }
        });
        document.getElementById('head-actions').style.display = 'flex';
      })
      .catch((err) => {
        document.getElementById('titulo-fornecedor').textContent = 'Erro ao carregar fornecedor';
        console.error(err);
      });

    fetch('listar-contratos-fornecedor.php')
      .then(r => r.json())
      .then(data => {
        if (!data.sucesso) throw new Error(data.erro || 'Falha ao listar contratos.');
        todosContratosDoFornecedor = (data.contratos || []).filter(c => String(c.fornecedor_id) === String(fornecedorId));
        renderContratos();
      })
      .catch(() => {
        document.getElementById('contratos-area').innerHTML = '<div class="empty-state" style="color:var(--red);">Falha ao carregar contratos do servidor.</div>';
      });
  }
</script>
</body>
</html>
