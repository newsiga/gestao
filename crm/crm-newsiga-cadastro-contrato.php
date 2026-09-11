<?php require_once __DIR__.'/auth.php'; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Novo Contrato</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Instrument+Serif:ital@1&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --cream:#f3f2ec; --forest:#0d2b22; --forest-soft:#123028; --lime:#b6ef2a;
    --muted:#6b7770; --card:#e7e6e0; --card-hover:#dedcd4; --border:#d9d8d1; --white:#ffffff;
    --red:#c0503e;
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
  .breadcrumb b{color:var(--forest); font-weight:600;}
  .page-head{padding:44px 0 8px;}
  .eyebrow{font-family:var(--font-ui); font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:14px;}
  h1{font-family:var(--font-display); font-weight:800; font-size:36px; letter-spacing:-.01em; line-height:1.15;}
  h1 em{font-family:var(--font-italic); font-style:italic; font-weight:400;}
  .page-sub{color:var(--muted); font-size:15px; margin-top:14px; max-width:56ch;}
  .layout{display:grid; grid-template-columns:1.5fr 1fr; gap:28px; margin:40px 0 80px; align-items:start;}
  .section{margin-bottom:36px;}
  .section-label{font-family:var(--font-ui); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); margin-bottom:14px; display:block;}
  .client-input{background:var(--white); border:1px solid var(--border); border-radius:10px; padding:16px 18px; display:flex; align-items:center; justify-content:space-between;}
  .client-input .name{font-family:var(--font-display); font-weight:700; font-size:16px;}
  .client-input .meta{color:var(--muted); font-size:13px; margin-top:2px;}
  .type-grid{display:grid; grid-template-columns:1fr 1fr; gap:12px;}
  .type-card{background:var(--card); border:2px solid transparent; border-radius:10px; padding:18px 18px; cursor:pointer; transition:all .15s;}
  .type-card:hover{background:var(--card-hover);}
  .type-card.selected{background:var(--forest); border-color:var(--forest);}
  .type-card h4{font-family:var(--font-display); font-weight:700; font-size:15px; margin-bottom:6px;}
  .type-card.selected h4{color:var(--white);}
  .type-card p{font-size:13px; color:var(--muted);}
  .type-card.selected p{color:#b8c4be;}
  .field{margin-bottom:18px;}
  .field label{display:block; font-family:var(--font-ui); font-size:13px; font-weight:600; color:var(--forest); margin-bottom:8px;}
  .field .hint{font-size:12.5px; color:var(--muted); margin-top:6px;}
  .field input, .field select{width:100%; height:46px; border-radius:8px; border:1px solid var(--border); background:var(--white); padding:0 14px; font-size:14.5px; font-family:var(--font-ui); color:var(--forest);}
  .field input:focus, .field select:focus{outline:none; border-color:var(--forest);}
  .row2{display:grid; grid-template-columns:1fr 1fr; gap:14px;}
  .type-fields{display:none;}
  .type-fields.active{display:block;}
  .installments{border:1px solid var(--border); border-radius:10px; overflow:hidden; background:var(--white);}
  .inst-row{display:grid; grid-template-columns:32px 1fr 1fr auto; gap:10px; align-items:center; padding:12px 16px; border-bottom:1px solid var(--border);}
  .inst-row:last-child{border-bottom:none;}
  .inst-row .num{font-family:var(--font-display); font-weight:700; color:var(--muted); font-size:14px;}
  .inst-row input{height:38px; font-size:13.5px;}
  .inst-row .remove{color:var(--red); font-size:13px; cursor:pointer; font-weight:600;}
  .add-installment{padding:12px 16px; font-size:13px; color:var(--forest); font-weight:700; cursor:pointer; text-align:center;}
  .status-toggle{display:flex; gap:10px;}
  .status-opt{flex:1; text-align:center; padding:12px; border-radius:8px; border:1px solid var(--border); background:var(--white); font-size:13.5px; font-weight:600; color:var(--muted); cursor:pointer;}
  .status-opt.selected{background:var(--forest); color:var(--white); border-color:var(--forest);}
  .summary-card{background:var(--forest-soft); border-radius:14px; padding:30px; position:sticky; top:24px;}
  .summary-eyebrow{font-family:var(--font-ui); font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--lime); margin-bottom:16px;}
  .summary-card h3{font-family:var(--font-display); color:var(--white); font-size:20px; font-weight:700; margin-bottom:22px; line-height:1.3;}
  .actions{display:flex; gap:12px; margin-top:32px;}
  .btn-primary{font-family:var(--font-ui); font-weight:700; font-size:14.5px; background:var(--forest); color:var(--white); border:none; padding:15px 26px; border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; gap:8px;}
  .btn-primary:disabled{opacity:.6; cursor:not-allowed;}
  .btn-secondary{font-family:var(--font-ui); font-weight:600; font-size:14.5px; background:transparent; color:var(--forest); border:1px solid var(--border); padding:15px 22px; border-radius:8px; cursor:pointer;}
  .form-msg{margin-top:16px; padding:14px 16px; border-radius:8px; font-size:13.5px; display:none;}
  .form-msg.ok{background:#dbe9d8; color:#2f5c3f; display:block;}
  .form-msg.error{background:#f1d9d4; color:var(--red); display:block;}
  @media (max-width:860px){.layout{grid-template-columns:1fr;} .type-grid,.row2{grid-template-columns:1fr;} h1{font-size:28px;} .summary-card{position:static;}}
</style>
</head>
<body>

<nav>
  <div class="wrap">
    <a class="logo" href="crm-newsiga-painel.php">crm<span>.newsiga — uso interno</span></a>
    <div class="breadcrumb" style="display:flex; align-items:center; gap:24px;">
      <a href="crm-newsiga-painel.php" style="color:var(--muted); text-decoration:none; font-weight:600;">Painel</a>
      <a href="crm-newsiga-cadastro-cliente.php" style="color:var(--muted); text-decoration:none; font-weight:600;">Clientes</a>
      <a href="crm-newsiga-contratos.php" style="color:var(--muted); text-decoration:none; font-weight:600;">Contratos</a> <a href="crm-newsiga-prospects.php" style="color:var(--muted); text-decoration:none; font-weight:600;">Prospects</a> <span style="color:var(--muted);">/ <b style="color:var(--forest);">Novo contrato</b></span>
    </div>
  </div>
</nav>

<div class="wrap">
  <div class="page-head">
    <div class="eyebrow">cadastro de contrato</div>
    <h1>Um contrato novo para um cliente <em>já conhecido</em>.</h1>
    <p class="page-sub">Cada cliente pode ter mais de um contrato ativo ao mesmo tempo — este formulário grava um contrato novo, sem afetar os demais.</p>
  </div>

  <form id="contrato-form">
    <div class="layout">
      <div>
        <div class="section">
          <span class="section-label">Cliente</span>
          <select name="cliente_id" id="cliente-select" required class="field" style="width:100%; height:46px; border-radius:10px; border:1px solid var(--border); background:var(--white); padding:0 14px; font-size:14.5px; font-family:var(--font-ui); color:var(--forest);">
            <option value="">Carregando clientes...</option>
          </select>
          <div class="hint" style="margin-top:8px;">Não encontrou? <a href="crm-newsiga-cadastro-cliente.php" style="color:var(--forest); font-weight:600;">cadastrar novo cliente</a>.</div>
        </div>

        <div class="section">
          <span class="section-label">Tipo de contrato</span>
          <input type="hidden" name="tipo" id="tipo-input" value="banco_horas_minimo">
          <div class="type-grid" style="grid-template-columns:1fr 1fr 1fr;">
            <div class="type-card" data-tipo="mensalidade_fixa"><h4>Valor fixo</h4><p>Consumo ilimitado; faturamento fixo, independente do uso.</p></div>
            <div class="type-card" data-tipo="hora_aberta"><h4>Hora aberta</h4><p>Sem banco, sem excedente — só consumo × taxa única.</p></div>
            <div class="type-card selected" data-tipo="banco_horas_minimo"><h4>Banco com mínimo</h4><p>Mínimo garantido faturado sempre; excedente em taxa própria.</p></div>
            <div class="type-card" data-tipo="banco_horas_consumo"><h4>Banco sem mínimo</h4><p>Fatura só o consumido dentro do banco; excedente em taxa própria.</p></div>
            <div class="type-card" data-tipo="projeto_parcelado"><h4>Projeto parcelado</h4><p>Valor total dividido em parcelas com datas próprias.</p></div>
          </div>
        </div>

        <div class="section">
          <span class="section-label">Condições do contrato</span>

          <div class="type-fields" data-for="mensalidade_fixa">
            <div class="row2">
              <div class="field"><label>Valor mensal (R$)</label><input type="text" inputmode="decimal" class="money-input" name="valor" placeholder="R$ 0,00"></div>
              <div class="field"><label>Dia de vencimento</label><input type="number" min="1" max="31" name="dia_vencimento" placeholder="5"></div>
            </div>
          </div>

          <div class="type-fields" data-for="hora_aberta">
            <div class="row2">
              <div class="field"><label>Valor por hora (R$)</label><input type="text" inputmode="decimal" class="money-input" name="valor_hora" placeholder="R$ 0,00"></div>
              <div class="field"><label>Dia de vencimento</label><input type="number" min="1" max="31" name="dia_vencimento" placeholder="5"></div>
            </div>
            <div class="field"><label>Nome do contrato no Movidesk <span style="color:var(--muted); font-weight:400;">— opcional, necessário só pro fechamento automático</span></label><input type="text" name="movidesk_contract_name" placeholder="ex: Suporte Mari Louças"></div>
          </div>

          <div class="type-fields" data-for="banco_horas_consumo">
            <div class="row2">
              <div class="field"><label>Tamanho do banco (horas)</label><input type="number" step="0.01" name="horas_banco" placeholder="100"></div>
              <div class="field"><label>Dia de vencimento</label><input type="number" min="1" max="31" name="dia_vencimento" placeholder="5"></div>
            </div>
            <div class="row2">
              <div class="field"><label>Valor por hora — dentro do banco (R$)</label><input type="text" inputmode="decimal" class="money-input" name="valor_hora" placeholder="R$ 0,00"></div>
              <div class="field"><label>Valor por hora — excedente ao banco (R$)</label><input type="text" inputmode="decimal" class="money-input" name="valor_hora_excedente" placeholder="R$ 0,00"></div>
            </div>
            <div class="hint">Sem cobrança fixa — o faturamento do mês é sempre proporcional ao que foi consumido, seja dentro ou fora do banco.</div>
            <div class="field"><label>Nome do contrato no Movidesk <span style="color:var(--muted); font-weight:400;">— opcional, necessário só pro fechamento automático</span></label><input type="text" name="movidesk_contract_name" placeholder="ex: Suporte Mari Louças"></div>
          </div>

          <div class="type-fields active" data-for="banco_horas_minimo">
            <div class="row2">
              <div class="field"><label>Valor mínimo garantido (R$)</label><input type="text" inputmode="decimal" class="money-input" name="valor" placeholder="R$ 0,00"></div>
              <div class="field"><label>Quantidade de horas do pacote <span style="color:var(--muted); font-weight:400;">— necessário se houver excedente</span></label><input type="number" step="0.01" name="horas_minimas" placeholder="8"></div>
            </div>
            <div class="field"><label>Valor por hora excedente (R$) <span style="color:var(--muted); font-weight:400;">— opcional</span></label><input type="text" inputmode="decimal" class="money-input" name="valor_hora" placeholder="R$ 0,00 (opcional)"></div>
            <div class="field">
              <label>Dia de vencimento da fatura mensal</label>
              <input type="number" min="1" max="31" name="dia_vencimento" value="5" style="max-width:120px;">
              <div class="hint">Editável a qualquer momento — inclusive depois que o contrato estiver ativo.</div>
            </div>
            <div class="field"><label>Nome do contrato no Movidesk <span style="color:var(--muted); font-weight:400;">— opcional, necessário só pro fechamento automático</span></label><input type="text" name="movidesk_contract_name" placeholder="ex: Suporte Mari Louças"></div>
          </div>

          <div class="type-fields" data-for="projeto_parcelado">
            <div class="row2">
              <div class="field"><label>Valor total do projeto (R$)</label><input type="text" inputmode="decimal" class="money-input" id="parcela_valor_total" name="parcela_valor_total" placeholder="R$ 0,00"></div>
              <div class="field"><label>Quantidade de parcelas</label>
                <select id="parcela_qtd" name="parcela_qtd">
                  <option value="">Preencha o valor total primeiro</option>
                </select>
              </div>
            </div>
            <div class="field">
              <label>Vencimento da 1ª parcela</label>
              <input type="date" id="parcela_primeira_data" name="parcela_primeira_data" style="max-width:200px;">
              <div class="hint">As demais parcelas caem no mesmo dia dos meses seguintes. Se cair em fim de semana ou feriado nacional, o sistema empurra pro próximo dia útil — igual o ASAAS faz.</div>
            </div>
            <div class="installments" id="parcelas-preview" style="display:none; margin-top:14px;">
              <div class="ch-head" style="padding:10px 16px; background:var(--card); font-family:var(--font-ui); font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; font-weight:700;">Prévia (calculada no navegador — a data final é confirmada ao salvar)</div>
              <div id="parcelas-preview-rows"></div>
            </div>
          </div>
        </div>

        <div class="section">
          <span class="section-label">Detalhes</span>
          <div class="field"><label>Descrição do contrato</label><input type="text" name="descricao" placeholder="ex: Suporte mensal, Implantação Ativo Fixo..."></div>
          <div class="field">
            <label>Descrição do serviço (vai na cobrança do ASAAS)</label>
            <input type="text" name="descricao_servico" placeholder="ex: Serviço de suporte e manutenção do sistema Protheus da TOTVS.">
            <div class="hint">Texto formal que o cliente vê no boleto/cobrança. Se deixar em branco, usa um texto genérico ("Newsiga — Cliente — competência").</div>
          </div>
          <div class="field"><label>Origem (proposta vinculada, opcional)</label><input type="text" name="origem_proposta" placeholder="ex: PRP-2026_0098_v1"></div>
        </div>

        <div class="section">
          <span class="section-label">Status inicial</span>
          <input type="hidden" name="status" id="status-input" value="rascunho">
          <div class="status-toggle">
            <div class="status-opt selected" data-status="rascunho">Rascunho</div>
            <div class="status-opt" data-status="aprovado">Aprovado</div>
          </div>
          <div class="hint" style="margin-top:10px;">Contratos "aprovados" disparam a tela de confirmação de cadastro no ASAAS.</div>
        </div>

        <div class="actions">
          <button type="submit" class="btn-primary" id="submit-btn">Salvar contrato ↗</button>
          <button type="button" class="btn-secondary">Cancelar</button>
        </div>
        <div class="form-msg" id="form-msg"></div>
      </div>

      <div>
        <div class="summary-card">
          <div class="summary-eyebrow">resumo</div>
          <h3>O que será gravado no banco ao salvar</h3>
          <div style="font-size:13.5px; color:#dfe6e2; line-height:1.7;">
            Um novo registro em <b style="color:var(--lime);">contratos</b>, vinculado ao cliente selecionado, com <b style="color:var(--lime);">faturamento_gerenciado_por = 'sistema'</b> por padrão. Se o tipo for "projeto parcelado", cada linha da tabela ao lado vira um registro em <b style="color:var(--lime);">parcelas</b>, na mesma transação — ou nenhum dos dois é gravado, ou os dois são.
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
  // ---- Máscara monetária (R$ 0,00) ----
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
  function ligarMascaraEm(el) {
    el.addEventListener('input', () => aplicarMascaraMoeda(el));
  }
  document.querySelectorAll('.money-input').forEach(ligarMascaraEm);

  // ---- Carrega clientes reais do banco no seletor ----
  const clienteIdPreSelecionado = new URLSearchParams(window.location.search).get('cliente_id');
  fetch('listar-clientes.php')
    .then(r => r.json())
    .then(data => {
      const select = document.getElementById('cliente-select');
      if (!data.sucesso || !data.clientes || data.clientes.length === 0) {
        select.innerHTML = '<option value="">Nenhum cliente cadastrado ainda</option>';
        return;
      }
      select.innerHTML = '<option value="" selected disabled>Selecione o cliente...</option>' + data.clientes.map(c =>
        `<option value="${c.id}">${c.nome}${c.cnpj ? ' — ' + c.cnpj : ''}${c.asaas_customer_id ? ' (já no ASAAS)' : ''}</option>`
      ).join('');
      if (clienteIdPreSelecionado) {
        select.value = clienteIdPreSelecionado;
      }
    })
    .catch(() => {
      document.getElementById('cliente-select').innerHTML = '<option value="">Falha ao carregar clientes</option>';
    });

  // ---- Seleção de tipo de contrato: alterna campos condicionais ----
  document.querySelectorAll('.type-card').forEach(card => {
    card.addEventListener('click', () => {
      document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      const tipo = card.dataset.tipo;
      document.getElementById('tipo-input').value = tipo;
      document.querySelectorAll('.type-fields').forEach(f => {
        const isActive = f.dataset.for === tipo;
        f.classList.toggle('active', isActive);
        // Desabilita os campos do bloco inativo — senão, mesmo escondido,
        // ele seria enviado junto no submit e conflitaria com o campo de
        // mesmo "name" do bloco realmente selecionado.
        f.querySelectorAll('input').forEach(input => { input.disabled = !isActive; });
      });
    });
  });

  // Desabilita, já no carregamento da página, os campos de todo bloco que
  // não começa ativo (evita enviar os placeholders de tipos não selecionados
  // caso o usuário nunca clique em nenhum card antes de salvar).
  document.querySelectorAll('.type-fields:not(.active) input').forEach(input => { input.disabled = true; });

  // ---- Status inicial ----
  document.querySelectorAll('.status-opt').forEach(opt => {
    opt.addEventListener('click', () => {
      document.querySelectorAll('.status-opt').forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      document.getElementById('status-input').value = opt.dataset.status;
    });
  });

  // ---- Popula o seletor "Quantidade de parcelas" com "Nx de R$ ..." ----
  // (mesma ideia do dropdown "Parcelamento" do ASAAS: mostra o valor por
  // parcela já na opção, sem precisar rolar até a prévia detalhada.)
  const MAX_PARCELAS = 24;
  function atualizarOpcoesParcelas() {
    const select = document.getElementById('parcela_qtd');
    const valorTotal = parseFloat(moedaParaDecimal(document.getElementById('parcela_valor_total').value));
    const selecionadoAntes = select.value;

    if (!valorTotal || valorTotal <= 0) {
      select.innerHTML = '<option value="">Preencha o valor total primeiro</option>';
      return;
    }

    let html = '';
    for (let n = 1; n <= MAX_PARCELAS; n++) {
      const valorParcela = Math.floor((valorTotal * 100) / n) / 100; // valor-base (a última parcela absorve centavos, calculado de verdade no servidor)
      const label = n === 1
        ? `À vista — R$ ${valorParcela.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`
        : `${n}x de R$ ${valorParcela.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
      html += `<option value="${n}">${label}</option>`;
    }
    select.innerHTML = html;
    // Mantém a quantidade escolhida antes, se ela ainda existir na lista nova
    if (selecionadoAntes && selecionadoAntes <= MAX_PARCELAS) select.value = selecionadoAntes;
    atualizarPreviewParcelas();
  }
  document.getElementById('parcela_valor_total').addEventListener('input', atualizarOpcoesParcelas);
  document.getElementById('parcela_qtd').addEventListener('change', atualizarPreviewParcelas);

  // ---- Prévia automática das parcelas (projeto_parcelado) ----
  // Réplica em JS só pra visualização — o cálculo que vale é o do servidor
  // (salvar-contrato.php), inclusive a checagem de feriados nacionais.
  function dividirValorPreview(valorTotal, numParcelas) {
    const totalCentavos = Math.round(valorTotal * 100);
    const baseCentavos = Math.floor(totalCentavos / numParcelas);
    const valores = new Array(numParcelas).fill(baseCentavos);
    const diferenca = totalCentavos - (baseCentavos * numParcelas);
    valores[numParcelas - 1] += diferenca;
    return valores.map(c => c / 100);
  }
  function proximoDiaUtilPreview(data) {
    // Só fim de semana aqui no preview do navegador — feriados nacionais
    // são conferidos de verdade no servidor ao salvar.
    while (data.getDay() === 0 || data.getDay() === 6) {
      data.setDate(data.getDate() + 1);
    }
    return data;
  }
  function atualizarPreviewParcelas() {
    const valorTotalStr = document.getElementById('parcela_valor_total').value;
    const qtd = parseInt(document.getElementById('parcela_qtd').value, 10);
    const primeiraDataStr = document.getElementById('parcela_primeira_data').value;
    const preview = document.getElementById('parcelas-preview');
    const rows = document.getElementById('parcelas-preview-rows');

    const valorTotal = parseFloat(moedaParaDecimal(valorTotalStr));
    if (!valorTotal || !qtd || qtd < 1 || !primeiraDataStr) {
      preview.style.display = 'none';
      return;
    }

    const valores = dividirValorPreview(valorTotal, qtd);
    const [ano, mes, dia] = primeiraDataStr.split('-').map(Number);
    const diaOriginal = dia;
    let html = '';
    for (let i = 0; i < qtd; i++) {
      const mesAlvo = mes - 1 + i;
      const dataBase = new Date(ano + Math.floor(mesAlvo / 12), mesAlvo % 12, 1);
      const ultimoDiaDoMes = new Date(dataBase.getFullYear(), dataBase.getMonth() + 1, 0).getDate();
      dataBase.setDate(Math.min(diaOriginal, ultimoDiaDoMes));
      proximoDiaUtilPreview(dataBase);
      const dataFormatada = dataBase.toLocaleDateString('pt-BR');
      const valorFormatado = 'R$ ' + valores[i].toLocaleString('pt-BR', {minimumFractionDigits: 2});
      html += `<div class="charge-row" style="display:flex; justify-content:space-between; padding:10px 16px; font-size:13px; border-top:1px solid var(--border);"><span>Parcela ${i + 1} — ${dataFormatada}</span><span style="font-family:var(--font-display); font-weight:700;">${valorFormatado}</span></div>`;
    }
    rows.innerHTML = html;
    preview.style.display = 'block';
  }
  ['parcela_valor_total', 'parcela_qtd', 'parcela_primeira_data'].forEach(id => {
    document.getElementById(id).addEventListener('input', atualizarPreviewParcelas);
  });

  // ---- Envio real para salvar-contrato.php ----
  document.getElementById('contrato-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    const msg = document.getElementById('form-msg');
    btn.disabled = true;
    btn.textContent = 'Salvando...';
    msg.className = 'form-msg';

    try {
      // Converte "R$ 8.500,00" -> "8500.00" antes de enviar (o PHP espera
      // decimal com ponto). Guarda o valor mascarado pra restaurar depois.
      const camposMoeda = Array.from(document.querySelectorAll('.money-input')).filter(el => !el.disabled);
      const valoresOriginais = camposMoeda.map(el => el.value);
      camposMoeda.forEach(el => { el.value = moedaParaDecimal(el.value); });

      const formData = new FormData(e.target);

      camposMoeda.forEach((el, i) => { el.value = valoresOriginais[i]; });
      const resp = await fetch('salvar-contrato.php', { method: 'POST', body: formData });
      const data = await resp.json();

      if (!resp.ok) {
        msg.className = 'form-msg error';
        msg.textContent = (data.detalhes ? data.detalhes.join(' ') : data.erro) || 'Erro ao salvar.';
      } else {
        msg.className = 'form-msg ok';
        msg.textContent = `Contrato #${data.contrato_id} salvo.` + (data.redirecionar_para_confirmacao_asaas ? ' Redirecionando para confirmação ASAAS...' : '');
        if (data.redirecionar_para_confirmacao_asaas) {
          setTimeout(() => { window.location.href = `crm-newsiga-confirmacao-asaas.php?contrato=${data.contrato_id}`; }, 1200);
        }
      }
    } catch (err) {
      msg.className = 'form-msg error';
      msg.textContent = 'Falha de conexão com o servidor.';
    } finally {
      btn.disabled = false;
      btn.textContent = 'Salvar contrato ↗';
    }
  });
</script>
</body>
</html>
