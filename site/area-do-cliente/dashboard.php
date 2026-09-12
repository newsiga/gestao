<?php require __DIR__.'/bootstrap.php';require_login();$u=$_SESSION['user'];$company=null;$contract=null;$error='';$month=preg_match('/^\d{4}-\d{2}$/',$_GET['month']??'')?$_GET['month']:date('Y-m');$consumed=0.0;$contracted=0.0;$available=0.0;$extra=0.0;$tickets=[];$periodClosed=false;$monitor=[];$monitorTotals=['contracted'=>0.0,'consumed'=>0.0,'alerts'=>0,'exceeded'=>0,'value'=>0.0];if($u['role']==='client'&&$u['company_id']){$st=$pdo->prepare('SELECT * FROM companies WHERE id=?');$st->execute([$u['company_id']]);$company=$st->fetch();$st=$pdo->prepare("SELECT * FROM contracts WHERE company_id=? AND status='active' ORDER BY id DESC LIMIT 1");$st->execute([$u['company_id']]);$contract=$st->fetch();if($contract){require __DIR__.'/movidesk.php';require __DIR__.'/portal-data.php';try{$usage=portal_consumption($contract['name'],$month);$periodClosed=portal_period_is_closed($usage['raw']);$consumed=$usage['consumed'];$contracted=(float)($usage['raw']['contractedHours']??$contract['monthly_hours']);$available=max(0,$contracted-$consumed);$extra=max(0,$consumed-$contracted);$tickets=portal_group_tickets($usage['appointments']);}catch(Throwable $e){error_log('Newsiga dashboard Movidesk: '.$e->getMessage());$error='Não foi possível atualizar o consumo agora. Os demais dados do seu acesso continuam disponíveis.';}}}elseif($u['role']==='admin'){require __DIR__.'/movidesk.php';require __DIR__.'/portal-data.php';$contracts=$pdo->query("SELECT ct.*,c.name company,c.slug company_slug,c.status company_status FROM contracts ct JOIN companies c ON c.id=ct.company_id WHERE ct.status='active' AND c.status='active' ORDER BY c.name,ct.name")->fetchAll();foreach($contracts as $adminContract){$item=['contract'=>$adminContract,'error'=>'','contracted'=>(float)$adminContract['monthly_hours'],'consumed'=>0.0,'available'=>(float)$adminContract['monthly_hours'],'extra'=>0.0,'percent'=>0.0,'value'=>0.0,'differentiated'=>false,'closed'=>false];try{$usage=portal_consumption((string)$adminContract['name'],$month);$item['closed']=portal_period_is_closed($usage['raw']);$item['contracted']=(float)($usage['raw']['contractedHours']??$adminContract['monthly_hours']);$item['consumed']=(float)$usage['consumed'];$item['available']=max(0,$item['contracted']-$item['consumed']);$item['extra']=max(0,$item['consumed']-$item['contracted']);$item['percent']=$item['contracted']>0?($item['consumed']/$item['contracted'])*100:0;$adminTickets=portal_group_tickets($usage['appointments']);$item['differentiated']=(bool)($usage['definition']['differentiateHoursFranchise']??false)&&!empty($usage['definition']['typeActivities']);if($item['differentiated']){$differentiatedResult=portal_differentiated_values($adminTickets,$usage['definition']);$item['value']=(float)$differentiatedResult[1];}else{[$adminTickets,$extraValue]=portal_ticket_values($adminTickets,$item['contracted'],(float)$adminContract['base_amount'],(float)$adminContract['excess_hour_amount']);if(portal_bills_by_consumption((string)($adminContract['company_slug']??''))){$baseValue=0.0;$extraValue=0.0;foreach($adminTickets as $adminTicket){$baseValue+=(float)($adminTicket['covered_hours']??0)*(float)$adminContract['base_amount'];$extraValue+=(float)($adminTicket['extra_hours']??0)*(float)$adminContract['excess_hour_amount'];}$item['value']=round($baseValue+$extraValue,2);}else{if(portal_has_minimum_package((string)($adminContract['company_slug']??''))&&(float)($usage['raw']['exceededHourAmount']??0)>0)$extraValue=(float)$usage['raw']['exceededHourAmount'];$item['value']=round(($item['contracted']*(float)$adminContract['base_amount'])+$extraValue,2);}}$monitorTotals['contracted']+=$item['contracted'];$monitorTotals['consumed']+=$item['consumed'];$monitorTotals['value']+=$item['value'];if($item['extra']>0)$monitorTotals['exceeded']++;elseif($item['percent']>=80)$monitorTotals['alerts']++;}catch(Throwable $e){error_log('Newsiga admin monitor '.$adminContract['name'].': '.$e->getMessage());$item['error']='Dados temporariamente indisponíveis.';}$monitor[]=$item;}}$percent=$contracted>0?min(100,($consumed/$contracted)*100):0;
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>Painel | Newsiga</title>
<link rel="stylesheet" href="assets/style.css">
<?php if($u['role']==='admin'):?><link rel="stylesheet" href="assets/admin.css?v=7.5.3"><?php endif;?>
<style>.client-head{display:flex;align-items:end;justify-content:space-between;gap:24px}.period-form{display:flex;align-items:end;gap:10px;margin:0}.period-form label{min-width:190px}.period-form button{height:48px}.metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin:34px 0 18px}.metric{background:#fff;border:1px solid #d9dfda;padding:24px}.metric small{display:block;color:#64766f;font-weight:800;letter-spacing:.06em;margin-bottom:17px}.metric strong{font-size:28px}.metric.dark{background:#123d32;color:#fff}.metric.dark strong{color:#b9f545}.progress-panel{background:#fff;border:1px solid #d9dfda;padding:24px;margin-bottom:18px}.progress-labels{display:flex;justify-content:space-between;gap:16px;font-size:13px}.progress-track{height:12px;margin:12px 0;background:#e2e8e4;overflow:hidden}.progress-fill{height:100%;background:#2a9d78}.extra-note{color:#9a5b00;font-weight:700}.panel-head{display:flex;justify-content:space-between;align-items:center;gap:20px}.report-link{display:inline-flex;padding:11px 15px;border:1px solid #cbd5d0;color:#0c2a23;text-decoration:none;font-size:12px;font-weight:700}.ticket-link{color:#176c58;font-weight:800;text-decoration:none}.ticket-link:hover{text-decoration:underline}.ticket-meta{color:#64766f;font-size:11px}.empty{padding:28px 0;color:#64766f}.period-state{display:inline-flex;margin:0 0 18px;padding:6px 9px;background:#fff6dc;color:#705711;font-size:10px;font-weight:800;letter-spacing:.04em}.period-state.closed{background:#e4f4de;color:#28652d}@media(max-width:760px){.client-head,.panel-head{display:grid}.period-form{display:grid}.metrics{grid-template-columns:1fr}.panel{overflow:auto}}</style>
</head>
<body>
<header class="top">
<a class="brand" href="<?=url('dashboard.php')?>">newsiga
</a>
<nav>
<?php if($u['role']==='admin'):?>
<a href="<?=url('admin/')?>">Administração</a>
<?php endif;?>
<a href="<?=url('profile.php')?>">Minha conta</a>
<a href="<?=url('logout.php')?>">Sair</a>
</nav>
</header>
<main class="container">
<p class="kicker">ÁREA DO CLIENTE</p>
<?php if($u['role']==='admin'):?>
<h1>Olá, <?=e($u['name'])?>.</h1>
<section class="contract-monitor"><div class="monitor-heading"><div><p class="kicker">ACOMPANHAMENTO</p><h2>Horas dos contratos</h2><p>Visão consolidada dos contratos ativos no Movidesk.</p></div><form method="get" class="month-filter"><label>Competência<input type="month" name="month" value="<?=e($month)?>"></label><button class="primary">Consultar</button></form></div><div class="monitor-summary"><article><small>CONTRATOS ATIVOS</small><strong><?=count($monitor)?></strong></article><article><small>HORAS CONSUMIDAS</small><strong><?=portal_duration($monitorTotals['consumed'])?></strong><span>de <?=portal_duration($monitorTotals['contracted'])?></span></article><article><small>ATENÇÃO</small><strong><?=$monitorTotals['alerts']?></strong><span>acima de 80%</span></article><article class="<?=($monitorTotals['exceeded']>0)?'summary-danger':''?>"><small>COM EXCEDENTE</small><strong><?=$monitorTotals['exceeded']?></strong><span>contratos</span></article></div><div class="contract-monitor-grid"><?php foreach($monitor as $item):$c=$item['contract'];$state=$item['extra']>0?'exceeded':($item['percent']>=80?'warning':'healthy');?><a href="<?=url('relatorio.php?company_id='.(int)$c['company_id'].'&month='.urlencode($month))?>" class="contract-card <?=$state?>"><div class="contract-card-head"><div><h3><?=e($c['company'])?></h3><span><?=e($c['name'])?></span></div><?php if(!$item['error']):?><span class="health-badge"><?=$state==='exceeded'?'Excedente':($state==='warning'?'Atenção':'Dentro do limite')?></span><?php endif;?></div><?php if($item['error']):?><p class="monitor-error"><?=e($item['error'])?></p><?php else:?><div class="hours-row"><div><small>CONSUMIDAS</small><strong><?=portal_duration($item['consumed'])?></strong></div><div><small><?=$item['extra']>0?'EXCEDENTES':'DISPONÍVEIS'?></small><strong><?=portal_duration($item['extra']>0?$item['extra']:$item['available'])?></strong></div><div><small>CONTRATADAS</small><strong><?=portal_duration($item['contracted'])?></strong></div></div><div class="monitor-progress"><div class="monitor-progress-label"><span><?=number_format($item['percent'],0,',','.')?>% utilizado</span><strong>R$ <?=number_format($item['value'],2,',','.')?></strong></div><div class="monitor-track"><span style="width:<?=min(100,$item['percent'])?>%"></span></div><small><?=$item['closed']?'Fechamento oficial':'Prévia do período'?> · sem impostos<?=$item['differentiated']?' · tipos de hora diferenciados':''?> · Ver detalhes →</small></div><?php endif;?></a><?php endforeach;?><?php if(!$monitor):?><p class="monitor-empty">Nenhum contrato ativo para acompanhar.</p><?php endif;?></div></section>
<?php elseif($company):?>
<div class="client-head">
<div>
<h1>Olá, <?=e($u['name'])?>.</h1>
<p class="muted">
<?=e($company['name'])?> · <?=e($contract['name']??'Contrato não cadastrado')?>
</p>
</div>
<form class="period-form" method="get">
<label>Competência<input name="month" type="month" value="<?=e($month)?>">
</label>
<button class="button">Consultar</button>
</form>
</div>
<?php if($error):?>
<p class="error">
<?=e($error)?>
</p>
<?php endif;?>
<?php if($contract):?>
<section class="metrics">
<article class="metric">
<small>HORAS CONTRATADAS</small>
<strong>
<?=portal_duration($contracted)?>
</strong>
</article>
<article class="metric dark">
<small>HORAS CONSUMIDAS</small>
<strong>
<?=portal_duration($consumed)?>
</strong>
</article>
<article class="metric">
<small>
<?=($extra>0)?'HORAS EXCEDENTES':'HORAS DISPONÍVEIS'?>
</small>
<strong>
<?=portal_duration($extra>0?$extra:$available)?>
</strong>
</article>
</section>
<p class="period-state <?=$periodClosed?'closed':''?>"><?=$periodClosed?'Fechamento oficial':'Prévia do período'?></p>
<section class="progress-panel">
<div class="progress-labels">
<span>
<?=portal_duration($consumed)?> utilizadas de <?=portal_duration($contracted)?>
</span>
<strong>
<?=number_format($percent,0,',','.')?>%</strong>
</div>
<div class="progress-track">
<div class="progress-fill" style="width:<?=$percent?>%">
</div>
</div>
<?php if($extra>0):?>
<span class="extra-note">
<?=portal_duration($extra)?> excedentes no período</span>
<?php else:?>
<span class="muted">
<?=portal_duration($available)?> restantes no período</span>
<?php endif;?>
</section>
<section class="panel">
<div class="panel-head">
<div>
<p class="kicker" style="margin-top:0">ATENDIMENTOS</p>
<h2>Chamados da competência</h2>
</div>
<a class="report-link" href="<?=url('relatorio.php?month='.urlencode($month))?>">Imprimir / salvar PDF</a>
</div>
<?php if($tickets):?>
<table>
<thead>
<tr>
<th>Ticket</th>
<th>Data</th>
<th>Atividade</th>
<th>Tempo</th>
<th>Responsável</th>
</tr>
</thead>
<tbody>
<?php foreach($tickets as $ticket):?>
<tr>
<td>
<a class="ticket-link" href="<?=url('ticket.php?id='.urlencode($ticket['number']).'&month='.urlencode($month))?>">#<?=e($ticket['number'])?>
</a>
</td>
<td>
<?=e(($ticketDate=strtotime((string)$ticket['date']))?date('d/m/Y',$ticketDate):substr((string)$ticket['date'],0,10))?>
</td>
<td>
<?=e($ticket['activity'])?>
<div class="ticket-meta">
<?=e(implode(', ',array_keys($ticket['work_types'])))?>
</div>
</td>
<td>
<strong>
<?=portal_duration($ticket['hours'])?>
</strong>
</td>
<td>
<?=e(implode(', ',array_keys($ticket['responsibles'])))?>
</td>
</tr>
<?php endforeach;?>
</tbody>
</table>
<?php else:?>
<p class="empty">Nenhum apontamento encontrado para esta competência.</p>
<?php endif;?>
</section>
<?php endif;?>
<?php endif;?>
</main>
</body>
</html>
