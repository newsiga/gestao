<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Confirmar cadastro ASAAS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Instrument+Serif:ital@1&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --cream:#f3f2ec; --forest:#0d2b22; --forest-soft:#123028; --lime:#b6ef2a;
    --muted:#6b7770; --card:#e7e6e0; --border:#d9d8d1; --white:#ffffff;
    --amber-bg:#f3e6c9; --amber-text:#8a6414; --amber-border:#e3cc93; --red:#c0503e;
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
  .breadcrumb a{color:var(--muted); text-decoration:none; font-weight:600;}
  .breadcrumb b{color:var(--forest); font-weight:600;}
  .page-head{padding:44px 0 8px;}
  .eyebrow{font-family:var(--font-ui); font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:14px;}
  h1{font-family:var(--font-display); font-weight:800; font-size:32px; letter-spacing:-.01em; line-height:1.2;}
  h1 em{font-family:var(--font-italic); font-style:italic; font-weight:400;}
  .page-sub{color:var(--muted); font-size:15px; margin-top:12px;}
  .contract-ref{font-family:var(--font-ui); font-size:13px; color:var(--muted); font-weight:600; background:var(--card); border-radius:8px; padding:12px 16px; margin:28px 0 32px; display:inline-block;}
  .contract-ref b{color:var(--forest);}
  .layout{display:grid; grid-template-columns:1.4fr 1fr; gap:28px; margin-bottom:80px; align-items:start;}
  .section{margin-bottom:30px;}
  .section-label{font-family:var(--font-ui); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); margin-bottom:14px; display:block;}
  .field{margin-bottom:16px;}
  .field label{display:block; font-family:var(--font-ui); font-size:13px; font-weight:600; color:var(--forest); margin-bottom:8px;}
  .field input{width:100%; height:46px; border-radius:8px; border:1px solid var(--border); background:var(--white); padding:0 14px; font-size:14.5px; font-family:var(--font-ui); color:var(--forest);}
  .field input:focus{outline:none; border-color:var(--forest);}
  .row2{display:grid; grid-template-columns:1fr 1fr; gap:14px;}
  .alert-card{background:var(--amber-bg); border:1px solid var(--amber-border); border-radius:10px; padding:18px 20px; margin-bottom:16px;}
  .alert-head{display:flex; gap:10px; align-items:flex-start; margin-bottom:14px;}
  .alert-head .dot{width:7px; height:7px; border-radius:50%; background:var(--amber-text); margin-top:6px; flex-shrink:0;}
  .alert-head strong{font-family:var(--font-display); font-weight:700; font-size:14.5px; color:#5c4610; display:block; margin-bottom:3px;}
  .alert-head p{font-size:13.5px; color:#6b5620;}
  .decision-group{border-top:1px solid var(--amber-border); padding-top:14px; margin-top:14px;}
  .decision-label{font-family:var(--font-ui); font-size:11px; font-weight:700; color:#6b5620; text-transform:uppercase; letter-spacing:.05em; margin-bottom:10px;}
  .decision-opt{display:flex; align-items:flex-start; gap:10px; font-size:13.5px; color:#5c4610; background:var(--white); border:1px solid var(--amber-border); border-radius:7px; padding:12px 14px; margin-bottom:8px; cursor:pointer;}
  .decision-opt:last-child{margin-bottom:0;}
  .decision-opt.selected{border-color:var(--forest); box-shadow:0 0 0 1px var(--forest);}
  .decision-opt input{margin-top:2px; accent-color:var(--forest);}
  .decision-opt b{color:var(--forest); font-family:var(--font-display); font-weight:700;}
  .decision-opt .sub{display:block; font-size:12.5px; color:#8a7838; margin-top:2px;}
  .subscription-meta{display:flex; gap:16px; font-family:var(--font-ui); font-size:12.5px; color:#6b5620; margin-top:2px; flex-wrap:wrap;}
  .subscription-meta span b{color:#5c4610;}
  .charges-preview{border:1px solid var(--border); border-radius:10px; overflow:hidden; background:var(--white); margin-bottom:16px;}
  .charges-preview .ch-head{padding:12px 16px; background:var(--card); font-family:var(--font-ui); font-size:11.5px; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; font-weight:700;}
  .charge-row{display:flex; justify-content:space-between; padding:13px 16px; font-size:13.5px; border-top:1px solid var(--border);}
  .charge-row span:first-child{color:var(--muted);}
  .charge-row span:last-child{font-family:var(--font-display); font-weight:700;}
  .actions{display:flex; gap:12px; margin-top:8px;}
  .btn-primary{font-family:var(--font-ui); font-weight:700; font-size:14.5px; background:var(--forest); color:var(--white); border:none; padding:15px 26px; border-radius:8px; cursor:pointer; flex:1;}
  .btn-primary:disabled{opacity:.6; cursor:not-allowed;}
  .btn-secondary{font-family:var(--font-ui); font-weight:600; font-size:14.5px; background:transparent; color:var(--forest); border:1px solid var(--border); padding:15px 22px; border-radius:8px; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center;}
  .side-card{background:var(--forest-soft); border-radius:14px; padding:28px; position:sticky; top:24px;}
  .side-eyebrow{font-family:var(--font-ui); font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--lime); margin-bottom:16px;}
  .side-card h3{font-family:var(--font-display); color:var(--white); font-size:18px; font-weight:700; margin-bottom:20px; line-height:1.3;}
  .side-row{display:flex; justify-content:space-between; padding:11px 0; border-top:1px solid rgba(255,255,255,.12); font-size:13px;}
  .side-row:first-of-type{border-top:none;}
  .side-row span:first-child{color:#a9b8b0;}
  .side-row span:last-child{color:var(--white); font-weight:600;}
  .form-msg{margin-top:16px; padding:14px 16px; border-radius:8px; font-size:13.5px; display:none;}
  .form-msg.ok{background:#dbe9d8; color:#2f5c3f; display:block;}
  .form-msg.error{background:#f1d9d4; color:var(--red); display:block;}
  @media (max-width:860px){.layout{grid-template-columns:1fr;} .row2{grid-template-columns:1fr;} h1{font-size:26px;} .side-card{position:static;}}
</style>
</head>
<body>

<nav>
  <div class="wrap">
    <a class="logo" href="crm-newsiga-painel.php">crm<span>.newsiga — uso interno</span></a>
    <div class="breadcrumb"><a href="crm-newsiga-contratos.php">Contratos</a> / <b id="breadcrumb-cliente">Confirmar cadastro ASAAS</b></div>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div class="eyebrow">confirmação antes de criar no asaas — sandbox</div>
    <h1>Revise antes de <em>confirmar</em>.</h1>
    <p class="page-sub">Nada é criado ou alterado no ASAAS até você clicar em confirmar.</p>
  </div>

  <div id="loading-msg" style="color:var(--muted); font-style:italic;">Carregando dados do contrato...</div>

  <div id="conteudo" style="display:none;">
    <div class="contract-ref" id="contract-ref"></div>

    <div class="layout">
      <div>
        <div class="section">
          <span class="section-label">Dados do cliente</span>
          <div class="field"><label>Razão social</label><input type="text" id="nome"></div>
          <div class="row2">
            <div class="field"><label>CNPJ</label><input type="text" id="cnpj"></div>
            <div class="field"><label>E-mail de cobrança</label><input type="text" id="email"></div>
          </div>
        </div>

        <div class="section" id="secao-pendencias" style="display:none;">
          <span class="section-label">Pendências encontradas</span>
          <div id="divergencia-container"></div>
          <div id="assinatura-container"></div>
        </div>

        <div class="section" id="secao-cobrancas" style="display:none;">
          <span class="section-label">Cobranças que serão criadas ao confirmar</span>
          <div class="charges-preview">
            <div class="ch-head">Parcelas pendentes</div>
            <div id="cobrancas-rows"></div>
          </div>
        </div>

        <div class="actions">
          <a class="btn-secondary" href="crm-newsiga-editar-contrato.php" id="link-editar">Editar antes de enviar</a>
          <button class="btn-primary" id="submit-btn">Confirmar e criar no ASAAS ↗</button>
        </div>
        <div class="form-msg" id="form-msg"></div>
      </div>

      <div>
        <div class="side-card">
          <div class="side-eyebrow">o que vai acontecer</div>
          <h3 id="side-title">Resumo</h3>
          <div class="side-row"><span>Cliente ASAAS</span><span id="side-cliente">—</span></div>
          <div class="side-row"><span>Divergências</span><span id="side-divergencias">—</span></div>
          <div class="side-row"><span>Assinatura anterior</span><span id="side-assinatura">—</span></div>
          <div class="side-row"><span>Cobranças criadas agora</span><span id="side-cobrancas">—</span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const params = new URLSearchParams(window.location.search);
  const contratoId = params.get('id');
  let dadosPreparo = null;
  const decisoesDivergencia = {};
  let decisaoAssinatura = null;

  document.getElementById('link-editar').href = `crm-newsiga-editar-contrato.php?id=${contratoId}`;

  fetch('preparar-confirmacao-asaas.php?contrato_id=' + encodeURIComponent(contratoId))
    .then(r => r.json())
    .then(data => {
      if (!data.sucesso) {
        document.getElementById('loading-msg').textContent = data.erro || 'Erro ao carregar.';
        document.getElementById('loading-msg').style.color = 'var(--red)';
        return;
      }
      dadosPreparo = data;
      montarTela(data);
      document.getElementById('loading-msg').style.display = 'none';
      document.getElementById('conteudo').style.display = 'block';
    })
    .catch(() => {
      document.getElementById('loading-msg').textContent = 'Falha de conexão ao carregar.';
      document.getElementById('loading-msg').style.color = 'var(--red)';
    });

  function montarTela(data) {
    const c = data.contrato;
    document.getElementById('breadcrumb-cliente').textContent = c.cliente_nome;
    document.getElementById('contract-ref').innerHTML = `Contrato <b>#${c.id}</b> · ${c.cliente_nome} · ${c.descricao || 'sem descrição'} · aprovado`;
    document.getElementById('nome').value = c.cliente_nome;
    document.getElementById('cnpj').value = c.cliente_cnpj || '';
    document.getElementById('email').value = c.cliente_email || '';

    let temPendencia = false;
    const divContainer = document.getElementById('divergencia-container');
    if (data.divergencias && data.divergencias.length > 0) {
      temPendencia = true;
      data.divergencias.forEach(div => {
        decisoesDivergencia[div.campo] = 'manter_asaas'; // padrão mais seguro
        const el = document.createElement('div');
        el.className = 'alert-card';
        el.innerHTML = `
          <div class="alert-head"><div class="dot"></div><div>
            <strong>${div.rotulo} divergente do cadastro existente</strong>
            <p>Este cliente já é cliente ASAAS. O valor cadastrado lá é diferente do informado agora.</p>
          </div></div>
          <div class="decision-group">
            <div class="decision-label">Qual valor deve prevalecer?</div>
            <label class="decision-opt selected"><input type="radio" name="div-${div.campo}" value="manter_asaas" checked><span>Manter o que já está no ASAAS: <b>${div.no_asaas}</b></span></label>
            <label class="decision-opt"><input type="radio" name="div-${div.campo}" value="usar_local"><span>Usar o novo, informado agora: <b>${div.local}</b></span></label>
          </div>`;
        divContainer.appendChild(el);
        el.querySelectorAll('input[type="radio"]').forEach(radio => {
          radio.addEventListener('change', () => {
            decisoesDivergencia[div.campo] = radio.value;
            el.querySelectorAll('.decision-opt').forEach(o => o.classList.remove('selected'));
            radio.closest('.decision-opt').classList.add('selected');
            atualizarResumoLateral();
          });
        });
      });
    }

    const assinaturaContainer = document.getElementById('assinatura-container');
    const assinaturaAtiva = (data.assinaturas_existentes || []).find(a => a.status === 'ACTIVE');
    if (assinaturaAtiva) {
      temPendencia = true;
      decisaoAssinatura = 'excluir_automatico'; // padrão mais seguro: não mexe em nada existente
      const el = document.createElement('div');
      el.className = 'alert-card';
      el.innerHTML = `
        <div class="alert-head"><div class="dot"></div><div>
          <strong>Assinatura recorrente já ativa no ASAAS</strong>
          <p>Esse cliente já tem uma assinatura configurada direto no ASAAS. Isso não impede de seguir, mas define se este contrato entra no fechamento automático do sistema.</p>
        </div></div>
        <div class="subscription-meta">
          <span>id: <b>${assinaturaAtiva.id}</b></span>
          <span>ciclo: <b>${assinaturaAtiva.cycle || '—'}</b></span>
          <span>valor atual: <b>R$ ${Number(assinaturaAtiva.value || 0).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2})}</b></span>
        </div>
        <div class="decision-group">
          <div class="decision-label">O que fazer com essa assinatura?</div>
          <label class="decision-opt"><input type="radio" name="decisao-assinatura" value="suspender"><span><b>Suspender no ASAAS</b> e deixar o sistema gerenciar as cobranças deste cliente daqui pra frente.<span class="sub">Não apaga cobranças já geradas — só impede novas.</span></span></label>
          <label class="decision-opt selected"><input type="radio" name="decisao-assinatura" value="excluir_automatico" checked><span><b>Manter a assinatura como está</b> e excluir este contrato do fechamento automático do sistema.<span class="sub">Este contrato continua sendo cobrado pelo ASAAS diretamente.</span></span></label>
        </div>`;
      assinaturaContainer.appendChild(el);
      el.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', () => {
          decisaoAssinatura = radio.value;
          el.querySelectorAll('.decision-opt').forEach(o => o.classList.remove('selected'));
          radio.closest('.decision-opt').classList.add('selected');
          atualizarResumoLateral();
        });
      });
    }
    document.getElementById('secao-pendencias').style.display = temPendencia ? 'block' : 'none';

    if (data.cobrancas_preview && data.cobrancas_preview.length > 0) {
      document.getElementById('secao-cobrancas').style.display = 'block';
      document.getElementById('cobrancas-rows').innerHTML = data.cobrancas_preview.map(p => {
        const dataFmt = new Date(p.vencimento + 'T00:00:00').toLocaleDateString('pt-BR');
        const valorFmt = 'R$ ' + Number(p.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        return `<div class="charge-row"><span>Parcela ${p.numero} — ${dataFmt}</span><span>${valorFmt}</span></div>`;
      }).join('');
    }

    atualizarResumoLateral();
  }

  function atualizarResumoLateral() {
    const jaExiste = dadosPreparo.cliente_ja_existe_no_asaas;
    document.getElementById('side-cliente').textContent = jaExiste ? 'vincula ao existente' : 'cria novo';
    document.getElementById('side-divergencias').textContent = (dadosPreparo.divergencias || []).length === 0 ? 'nenhuma' : `${dadosPreparo.divergencias.length} pendente(s)`;
    document.getElementById('side-assinatura').textContent = decisaoAssinatura === 'suspender' ? 'será suspensa' : (decisaoAssinatura === 'excluir_automatico' ? 'permanece ativa' : '—');
    document.getElementById('side-cobrancas').textContent = (dadosPreparo.cobrancas_preview || []).length + ' parcela(s)';
  }

  document.getElementById('submit-btn').addEventListener('click', async () => {
    const btn = document.getElementById('submit-btn');
    const msg = document.getElementById('form-msg');
    btn.disabled = true;
    btn.textContent = 'Confirmando...';
    msg.className = 'form-msg';

    try {
      const fd = new FormData();
      fd.append('contrato_id', contratoId);
      fd.append('nome', document.getElementById('nome').value);
      fd.append('cnpj', document.getElementById('cnpj').value);
      fd.append('email', document.getElementById('email').value);
      fd.append('decisoes_divergencia', JSON.stringify(decisoesDivergencia));
      if (decisaoAssinatura) fd.append('decisao_assinatura', decisaoAssinatura);

      const resp = await fetch('confirmar-asaas.php', { method: 'POST', body: fd });
      const data = await resp.json();

      if (!resp.ok) {
        msg.className = 'form-msg error';
        msg.textContent = data.erro || 'Erro ao confirmar.';
      } else {
        msg.className = 'form-msg ok';
        msg.textContent = `Confirmado — cliente ASAAS #${data.asaas_customer_id}, ${data.cobrancas_criadas} cobrança(s) criada(s). Voltando pra lista...`;
        setTimeout(() => { window.location.href = 'crm-newsiga-contratos.php'; }, 1400);
        return;
      }
    } catch (err) {
      msg.className = 'form-msg error';
      msg.textContent = 'Falha de conexão com o servidor.';
    }
    btn.disabled = false;
    btn.textContent = 'Confirmar e criar no ASAAS ↗';
  });
</script>
</body>
</html>
