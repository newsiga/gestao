<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Editar Contrato</title>
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
  .wrap{max-width:1080px; margin:0 auto; padding:0 32px;}
  nav{border-bottom:1px solid var(--border); padding:22px 0;}
  nav .wrap{display:flex; align-items:center; justify-content:space-between;}
  .logo{font-family:var(--font-display); font-weight:800; font-size:19px; color:var(--forest); text-decoration:none;}
  .logo span{color:var(--muted); font-weight:600; font-size:13px; margin-left:8px;}
  .breadcrumb{font-family:var(--font-ui); font-size:13px; color:var(--muted); display:flex; align-items:center; gap:24px;}
  .breadcrumb a{color:var(--muted); text-decoration:none; font-weight:600;}
  .breadcrumb b{color:var(--forest); font-weight:600;}
  .page-head{padding:44px 0 8px;}
  .eyebrow{font-family:var(--font-ui); font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:14px;}
  h1{font-family:var(--font-display); font-weight:800; font-size:32px; letter-spacing:-.01em; line-height:1.2;}
  h1 em{font-family:var(--font-italic); font-style:italic; font-weight:400;}
  .page-sub{color:var(--muted); font-size:15px; margin-top:14px;}
  .layout{max-width:640px; margin:36px 0 80px;}
  .section{margin-bottom:30px;}
  .section-label{font-family:var(--font-ui); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); margin-bottom:14px; display:block;}
  .info-card{background:var(--white); border:1px solid var(--border); border-radius:10px; padding:16px 18px; display:flex; justify-content:space-between; align-items:center;}
  .info-card .name{font-family:var(--font-display); font-weight:700; font-size:16px;}
  .info-card .meta{color:var(--muted); font-size:13px; margin-top:2px;}
  .tipo-badge{font-family:var(--font-ui); font-size:12px; font-weight:600; padding:6px 12px; border-radius:6px; background:var(--card); color:var(--forest);}
  .field{margin-bottom:18px;}
  .field label{display:block; font-family:var(--font-ui); font-size:13px; font-weight:600; margin-bottom:8px;}
  .field input{width:100%; height:46px; border-radius:8px; border:1px solid var(--border); background:var(--white); padding:0 14px; font-size:14.5px; font-family:var(--font-ui); color:var(--forest);}
  .field input:focus{outline:none; border-color:var(--forest);}
  .field .hint{font-size:12.5px; color:var(--muted); margin-top:6px;}
  .row2{display:grid; grid-template-columns:1fr 1fr; gap:14px;}
  .actions{display:flex; gap:12px; margin-top:24px;}
  .btn-primary{font-family:var(--font-ui); font-weight:700; font-size:14.5px; background:var(--forest); color:var(--white); border:none; padding:15px 26px; border-radius:8px; cursor:pointer;}
  .btn-primary:disabled{opacity:.6; cursor:not-allowed;}
  .btn-secondary{font-family:var(--font-ui); font-weight:600; font-size:14.5px; background:transparent; color:var(--forest); border:1px solid var(--border); padding:15px 22px; border-radius:8px; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center;}
  .form-msg{margin-top:16px; padding:14px 16px; border-radius:8px; font-size:13.5px; display:none;}
  .form-msg.ok{background:#dbe9d8; color:#2f5c3f; display:block;}
  .form-msg.error{background:#f1d9d4; color:var(--red); display:block;}
  .notice{background:var(--card); border-radius:10px; padding:16px 18px; font-size:13.5px; color:var(--muted); margin-bottom:24px;}
</style>
</head>
<body>

<nav>
  <div class="wrap">
    <a class="logo" href="crm-newsiga-painel.php">crm<span>.newsiga — uso interno</span></a>
    <div class="breadcrumb">
      <a href="crm-newsiga-painel.php">Painel</a>
      <a href="crm-newsiga-contratos.php">Contratos</a>
      <span>/ <b id="breadcrumb-cliente">Editar contrato</b></span>
    </div>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div class="eyebrow">edição de contrato</div>
    <h1>Ajustar sem <em>refazer</em>.</h1>
    <p class="page-sub">Tipo e cliente não mudam por aqui — pra isso, encerre este contrato e crie um novo.</p>
  </div>

  <div class="layout" id="layout" style="display:none;">
    <div class="section">
      <span class="section-label">Contrato</span>
      <div class="info-card">
        <div>
          <div class="name" id="cliente-nome">—</div>
          <div class="meta" id="contrato-desc-atual">—</div>
        </div>
        <span class="tipo-badge" id="tipo-atual">—</span>
      </div>
    </div>

    <form id="contrato-edit-form">
      <input type="hidden" name="contrato_id" id="contrato-id">

      <div class="section" id="campos-editaveis">
        <span class="section-label">Condições do contrato</span>
        <!-- preenchido via JS conforme o tipo -->
      </div>

      <div class="section">
        <span class="section-label">Detalhes</span>
        <div class="field"><label>Descrição do contrato</label><input type="text" name="descricao" id="descricao"></div>
        <div class="field">
          <label>Descrição do serviço (vai na cobrança do ASAAS)</label>
          <input type="text" name="descricao_servico" id="descricao_servico" placeholder="ex: Serviço de suporte e manutenção do sistema Protheus da TOTVS.">
          <div class="hint">Texto formal que o cliente vê no boleto/cobrança. Em branco, usa um texto genérico.</div>
        </div>
        <div class="field"><label>Origem (proposta vinculada, opcional)</label><input type="text" name="origem_proposta" id="origem_proposta"></div>
      </div>

      <div class="actions">
        <button type="submit" class="btn-primary" id="submit-btn">Salvar alterações ↗</button>
        <a class="btn-secondary" href="crm-newsiga-contratos.php">Cancelar</a>
      </div>
      <div class="form-msg" id="form-msg"></div>
    </form>
  </div>

  <div id="loading-msg" style="color:var(--muted); font-style:italic; margin-top:20px;">Carregando contrato...</div>
</div>

<script>
  const params = new URLSearchParams(window.location.search);
  const contratoId = params.get('id');

  const tipoLabels = {
    mensalidade_fixa: 'Valor fixo',
    hora_aberta: 'Hora aberta',
    banco_horas_minimo: 'Banco com mínimo',
    banco_horas_consumo: 'Banco sem mínimo',
    projeto_parcelado: 'Projeto parcelado',
  };

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
  function valorParaMascara(v) {
    if (v === null || v === undefined || v === '') return '';
    return 'R$ ' + Number(v).toLocaleString('pt-BR', {minimumFractionDigits: 2});
  }

  function campoMoeda(name, label, valorAtual, opcional) {
    return `<div class="field"><label>${label}${opcional ? ' <span style="color:var(--muted); font-weight:400;">— opcional</span>' : ''}</label>
      <input type="text" inputmode="decimal" class="money-input" name="${name}" value="${valorParaMascara(valorAtual)}" placeholder="R$ 0,00"></div>`;
  }
  function campoVencimento(valorAtual) {
    return `<div class="field"><label>Dia de vencimento</label>
      <input type="number" min="1" max="31" name="dia_vencimento" value="${valorAtual ?? ''}" style="max-width:120px;"></div>`;
  }

  function campoMovidesk(valorAtual) {
    return `<div class="field"><label>Nome do contrato no Movidesk <span style="color:var(--muted); font-weight:400;">— opcional, necessário só pro fechamento automático</span></label>
      <input type="text" name="movidesk_contract_name" value="${valorAtual ?? ''}" placeholder="ex: Suporte Mari Louças"></div>`;
  }

  function montarCampos(c) {
    const container = document.getElementById('campos-editaveis');
    let html = '<span class="section-label">Condições do contrato</span>';

    if (c.tipo === 'mensalidade_fixa') {
      html += `<div class="row2">${campoMoeda('valor', 'Valor mensal (R$)', c.valor)}${campoVencimento(c.dia_vencimento)}</div>`;
    } else if (c.tipo === 'hora_aberta') {
      html += `<div class="row2">${campoMoeda('valor_hora', 'Valor por hora (R$)', c.valor_hora)}${campoVencimento(c.dia_vencimento)}</div>`;
      html += campoMovidesk(c.movidesk_contract_name);
    } else if (c.tipo === 'banco_horas_minimo') {
      html += `<div class="row2">${campoMoeda('valor', 'Valor mínimo garantido (R$)', c.valor)}<div class="field"><label>Quantidade de horas do pacote <span style="color:var(--muted); font-weight:400;">— necessário se houver excedente</span></label><input type="number" step="0.01" name="horas_minimas" value="${c.horas_minimas ?? ''}"></div></div>`;
      html += `<div class="row2">${campoMoeda('valor_hora', 'Valor por hora excedente (R$)', c.valor_hora, true)}${campoVencimento(c.dia_vencimento)}</div>`;
      html += campoMovidesk(c.movidesk_contract_name);
    } else if (c.tipo === 'banco_horas_consumo') {
      html += `<div class="row2"><div class="field"><label>Tamanho do banco (horas)</label><input type="number" step="0.01" name="horas_banco" value="${c.horas_banco ?? ''}"></div>${campoVencimento(c.dia_vencimento)}</div>`;
      html += `<div class="row2">${campoMoeda('valor_hora', 'Valor por hora — dentro do banco (R$)', c.valor_hora)}${campoMoeda('valor_hora_excedente', 'Valor por hora — excedente (R$)', c.valor_hora_excedente)}</div>`;
      html += campoMovidesk(c.movidesk_contract_name);
    } else if (c.tipo === 'projeto_parcelado') {
      html += `<div class="notice">Valor e data de cada parcela ainda não são editáveis individualmente — só o status. Isso já resolve o caso de contrato lançado retroativamente: marque como "Pago" as parcelas de meses que já passaram, pra elas não entrarem na geração de cobrança quando o contrato for aprovado.</div>`;
      html += `<div id="parcelas-lista" style="margin-top:16px;"><span class="section-label">Carregando parcelas...</span></div>`;
    }

    container.innerHTML = html;
    document.querySelectorAll('.money-input').forEach(el => {
      el.addEventListener('input', () => aplicarMascaraMoeda(el));
    });

    if (c.tipo === 'projeto_parcelado') {
      carregarParcelas(c.id);
    }
  }

  const statusParcelaLabels = { a_gerar: 'A gerar', gerado: 'Gerado', pago: 'Pago', atrasado: 'Atrasado' };

  function carregarParcelas(contratoId) {
    fetch('listar-parcelas.php?contrato_id=' + encodeURIComponent(contratoId))
      .then(r => r.json())
      .then(data => {
        const container = document.getElementById('parcelas-lista');
        if (!data.sucesso || !data.parcelas || data.parcelas.length === 0) {
          container.innerHTML = '<span style="color:var(--muted); font-size:13px;">Nenhuma parcela encontrada.</span>';
          return;
        }
        let html = '<div style="border:1px solid var(--border); border-radius:10px; overflow:hidden; background:var(--white);">';
        data.parcelas.forEach(p => {
          const dataFormatada = new Date(p.vencimento + 'T00:00:00').toLocaleDateString('pt-BR');
          const valorFormatado = 'R$ ' + Number(p.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2});
          html += `
            <div style="display:grid; grid-template-columns:40px 1fr 100px 140px; gap:12px; align-items:center; padding:12px 16px; border-bottom:1px solid var(--border);" data-parcela-row="${p.id}">
              <span style="font-family:var(--font-display); font-weight:700; color:var(--muted); font-size:13px;">${p.numero}</span>
              <span style="font-size:13px; color:var(--muted);">${dataFormatada}</span>
              <span style="font-family:var(--font-display); font-weight:700; font-size:13px;">${valorFormatado}</span>
              <select class="parcela-status-select" data-id="${p.id}" style="font-family:var(--font-ui); font-size:12.5px; font-weight:600; color:var(--forest); background:var(--white); border:1px solid var(--border); border-radius:6px; padding:6px 8px;">
                ${Object.keys(statusParcelaLabels).map(s => `<option value="${s}" ${s === p.status ? 'selected' : ''}>${statusParcelaLabels[s]}</option>`).join('')}
              </select>
            </div>`;
        });
        html += '</div>';
        container.innerHTML = html;

        document.querySelectorAll('.parcela-status-select').forEach(sel => {
          sel.addEventListener('change', async () => {
            const parcelaId = sel.dataset.id;
            const novoStatus = sel.value;
            sel.disabled = true;
            try {
              const fd = new FormData();
              fd.append('parcela_id', parcelaId);
              fd.append('status', novoStatus);
              const resp = await fetch('atualizar-parcela.php', { method: 'POST', body: fd });
              const respData = await resp.json();
              if (!resp.ok) {
                alert(respData.erro || 'Erro ao salvar.');
              } else if (respData.contrato_encerrado) {
                alert('Última parcela paga — este contrato foi encerrado automaticamente.');
                setTimeout(() => { window.location.href = 'crm-newsiga-contratos.php'; }, 300);
              } else if (respData.aviso) {
                // Aviso informativo, não bloqueia — só avisa que o ASAAS não foi tocado
                console.info(respData.aviso);
              }
            } catch (err) {
              alert('Falha de conexão ao salvar status da parcela.');
            } finally {
              sel.disabled = false;
            }
          });
        });
      })
      .catch(() => {
        document.getElementById('parcelas-lista').innerHTML = '<span style="color:var(--red); font-size:13px;">Falha ao carregar parcelas.</span>';
      });
  }

  fetch('buscar-contrato.php?id=' + encodeURIComponent(contratoId))
    .then(r => r.json())
    .then(data => {
      if (!data.sucesso) {
        document.getElementById('loading-msg').textContent = data.erro || 'Contrato não encontrado.';
        document.getElementById('loading-msg').style.color = 'var(--red)';
        return;
      }
      const c = data.contrato;
      document.getElementById('loading-msg').style.display = 'none';
      document.getElementById('layout').style.display = 'block';

      document.getElementById('cliente-nome').textContent = c.cliente_nome;
      document.getElementById('contrato-desc-atual').textContent = c.descricao || '(sem descrição)';
      document.getElementById('tipo-atual').textContent = tipoLabels[c.tipo] || c.tipo;
      document.getElementById('breadcrumb-cliente').textContent = c.cliente_nome;
      document.getElementById('contrato-id').value = c.id;
      document.getElementById('descricao').value = c.descricao || '';
      document.getElementById('descricao_servico').value = c.descricao_servico || '';
      document.getElementById('origem_proposta').value = c.origem_proposta || '';

      montarCampos(c);
    })
    .catch(() => {
      document.getElementById('loading-msg').textContent = 'Falha ao carregar contrato.';
      document.getElementById('loading-msg').style.color = 'var(--red)';
    });

  document.getElementById('contrato-edit-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    const msg = document.getElementById('form-msg');
    btn.disabled = true;
    btn.textContent = 'Salvando...';
    msg.className = 'form-msg';

    const camposMoeda = Array.from(document.querySelectorAll('.money-input'));
    const valoresOriginais = camposMoeda.map(el => el.value);
    camposMoeda.forEach(el => { el.value = moedaParaDecimal(el.value); });

    try {
      const formData = new FormData(e.target);
      const resp = await fetch('atualizar-contrato.php', { method: 'POST', body: formData });
      const data = await resp.json();

      if (!resp.ok) {
        msg.className = 'form-msg error';
        msg.textContent = (data.detalhes ? data.detalhes.join(' ') : data.erro) || 'Erro ao salvar.';
      } else {
        msg.className = 'form-msg ok';
        msg.textContent = 'Contrato atualizado. Voltando pra lista...';
        setTimeout(() => { window.location.href = 'crm-newsiga-contratos.php'; }, 900);
        return; // não reabilita o botão nem restaura os campos — já estamos saindo da tela
      }
    } catch (err) {
      msg.className = 'form-msg error';
      msg.textContent = 'Falha de conexão com o servidor.';
    } finally {
      camposMoeda.forEach((el, i) => { el.value = valoresOriginais[i]; });
      btn.disabled = false;
      btn.textContent = 'Salvar alterações ↗';
    }
  });
</script>
</body>
</html>
