<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Contratos de Fornecedor</title>
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
  .tipo-badge{font-family:var(--font-ui); font-size:11px; font-weight:600; padding:4px 10px; border-radius:6px; background:var(--card); color:var(--forest); white-space:nowrap;}

  .status-select{font-family:var(--font-ui); font-size:12.5px; font-weight:600; color:var(--forest); background:var(--white); border:1px solid var(--border); border-radius:6px; padding:6px 10px; cursor:pointer;}
  .row-msg{font-size:11.5px; color:var(--muted); margin-top:4px;}
  .row-msg.error{color:var(--red);}
  .row-msg.ok{color:#2f5c3f;}

  .empty-state{padding:60px 22px; text-align:center; color:var(--muted); font-size:14px;}

  tr.group-header td{background:var(--card); font-family:var(--font-display); font-weight:700; font-size:13.5px; padding:12px 22px; border-bottom:1px solid var(--border); cursor:pointer;}
  tr.group-header:hover td{background:#ddddd4;}
  tr.group-header .group-count{color:var(--muted); font-weight:600; font-size:12px; margin-left:8px;}
  tr.group-header .toggle-arrow{display:inline-block; width:14px; color:var(--muted); font-size:11px;}

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
      <a href="crm-newsiga-fornecedores.php">Fornecedores</a>
      <a href="crm-newsiga-despesas.php">Despesas</a>
      <a href="crm-newsiga-prospects.php">Prospects</a>
    </div>
    <a class="btn-nav" href="crm-newsiga-cadastro-contrato-fornecedor.php">+ Novo contrato</a>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div>
      <div class="eyebrow">todos os contratos de fornecedor</div>
      <h1>Rascunho vira <em>ativo</em>, um clique de cada vez.</h1>
    </div>
  </div>

  <div class="filters" id="filters">
    <button class="filter-btn active" data-status="">Todos</button>
    <button class="filter-btn" data-status="rascunho">Rascunho</button>
    <button class="filter-btn" data-status="ativo">Ativo</button>
    <button class="filter-btn" data-status="concluido">Concluído</button>
    <button class="filter-btn" data-status="encerrado">Encerrado</button>
  </div>

  <div class="panel">
    <table>
      <thead>
        <tr><th>Descrição</th><th>Tipo</th><th>Condições</th><th>Status</th><th></th></tr>
      </thead>
      <tbody id="contratos-tbody">
        <tr><td colspan="5" style="color:var(--muted); font-style:italic; padding:24px;">Carregando contratos...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  const tipoLabels = {
    mensalidade_fixa: 'valor fixo',
    hora_aberta: 'hora aberta',
  };
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
      return `${fmt(c.valor_hora)}/h · ${escopo}`;
    }
    if (c.tipo === 'mensalidade_fixa') return fmt(c.valor);
    return '—';
  }

  let todosContratos = [];
  let filtroAtivo = '';

  function renderizar() {
    const tbody = document.getElementById('contratos-tbody');
    const lista = filtroAtivo ? todosContratos.filter(c => c.status === filtroAtivo) : todosContratos;

    if (lista.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5"><div class="empty-state">Nenhum contrato' + (filtroAtivo ? ' com esse status' : ' cadastrado ainda') + '.</div></td></tr>';
      return;
    }

    const grupos = [];
    const indicePorFornecedor = {};
    lista.forEach(c => {
      const chave = c.fornecedor_id;
      if (!(chave in indicePorFornecedor)) {
        indicePorFornecedor[chave] = grupos.length;
        grupos.push({ fornecedor_id: c.fornecedor_id, fornecedor_nome: c.fornecedor_nome, contratos: [] });
      }
      grupos[indicePorFornecedor[chave]].contratos.push(c);
    });
    grupos.sort((a, b) => a.fornecedor_nome.localeCompare(b.fornecedor_nome, 'pt-BR'));

    tbody.innerHTML = grupos.map(grupo => {
      const linhasContrato = grupo.contratos.map(c => {
        const opcoes = (proximosStatus[c.status] || [c.status]).map(s =>
          `<option value="${s}" ${s === c.status ? 'selected' : ''}>${statusLabels[s]}</option>`
        ).join('');
        const botaoExcluir = c.status === 'rascunho'
          ? `<a href="#" class="excluir-link" data-id="${c.id}" style="color:var(--red); font-weight:600; font-size:13px; text-decoration:none; margin-left:14px;">excluir</a>`
          : '';
        return `
          <tr data-id="${c.id}" class="group-body" data-group="${grupo.fornecedor_id}" style="display:none;">
            <td style="color:var(--muted);">${c.descricao || '—'}</td>
            <td><span class="tipo-badge">${tipoLabels[c.tipo] || c.tipo}</span></td>
            <td>${condicoesDe(c)}</td>
            <td>
              <select class="status-select" data-id="${c.id}" data-atual="${c.status}">${opcoes}</select>
              <div class="row-msg" id="msg-${c.id}"></div>
            </td>
            <td><a href="crm-newsiga-cadastro-contrato-fornecedor.php?id=${c.id}" style="color:var(--forest); font-weight:600; font-size:13px; text-decoration:none;">editar</a>${botaoExcluir}</td>
          </tr>`;
      }).join('');

      const qtd = grupo.contratos.length;
      return `
        <tr class="group-header" data-group="${grupo.fornecedor_id}">
          <td colspan="5"><span class="toggle-arrow">▸</span>${grupo.fornecedor_nome}<span class="group-count">${qtd} contrato${qtd === 1 ? '' : 's'}</span></td>
        </tr>
        ${linhasContrato}`;
    }).join('');

    document.querySelectorAll('.group-header').forEach(row => {
      row.addEventListener('click', () => {
        const grupoId = row.dataset.group;
        const abrindo = row.classList.toggle('open');
        row.querySelector('.toggle-arrow').textContent = abrindo ? '▾' : '▸';
        document.querySelectorAll(`.group-body[data-group="${grupoId}"]`).forEach(linha => {
          linha.style.display = abrindo ? 'table-row' : 'none';
        });
      });
    });

    document.querySelectorAll('.excluir-link').forEach(link => {
      link.addEventListener('click', async (e) => {
        e.preventDefault();
        const contratoId = link.dataset.id;
        const confirmou = confirm('Excluir de vez este contrato de fornecedor? Essa ação não pode ser desfeita.');
        if (!confirmou) return;

        try {
          const fd = new FormData();
          fd.append('contrato_id', contratoId);
          const resp = await fetch('excluir-contrato-fornecedor.php', { method: 'POST', body: fd });
          const data = await resp.json();
          if (!resp.ok) { alert(data.erro || 'Erro ao excluir.'); return; }
          todosContratos = todosContratos.filter(c => String(c.id) !== String(contratoId));
          renderizar();
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
            const item = todosContratos.find(c => String(c.id) === String(contratoId));
            if (item) item.status = novoStatus;
            renderizar();
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

  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      filtroAtivo = btn.dataset.status;
      renderizar();
    });
  });

  fetch('listar-contratos-fornecedor.php')
    .then(r => r.json())
    .then(data => {
      todosContratos = (data.sucesso && data.contratos) ? data.contratos : [];
      renderizar();
    })
    .catch(() => {
      document.getElementById('contratos-tbody').innerHTML =
        '<tr><td colspan="5" style="color:var(--red); padding:24px;">Falha ao carregar contratos do servidor.</td></tr>';
    });
</script>
</body>
</html>
