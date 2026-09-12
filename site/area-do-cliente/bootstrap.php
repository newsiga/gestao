<?php
declare(strict_types=1);
$configFile=__DIR__.'/config.php';
if(!is_file($configFile)){http_response_code(503);exit('Portal ainda não configurado.');}
$config=require $configFile;
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive');
ini_set('session.use_strict_mode','1');
session_set_cookie_params(['lifetime'=>0,'path'=>$config['base_url'].'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']);
session_start();
if(!empty($_SESSION['last_activity'])&&time()-(int)$_SESSION['last_activity']>3600){$_SESSION=[];if(ini_get('session.use_cookies')){$params=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$params['path'],$params['domain'],$params['secure'],$params['httponly']);}session_destroy();session_start();}
$_SESSION['last_activity']=time();
$pdo=new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',$config['db_user'],$config['db_pass'],[
 PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false
]);
function e(?string $v):string{return htmlspecialchars($v??'',ENT_QUOTES,'UTF-8');}
function url(string $path=''):string{global $config;return rtrim($config['base_url'],'/').'/'.ltrim($path,'/');}
function csrf():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function check_csrf():void{if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')){http_response_code(419);exit('Sessão expirada. Volte e tente novamente.');}}
function require_login():void{if(empty($_SESSION['user'])){header('Location: '.url());exit;}}
function require_admin():void{require_login();if(($_SESSION['user']['role']??'')!=='admin'){http_response_code(403);exit('Acesso restrito.');}}
function audit(string $action,string $entity,?int $entityId=null,?string $details=null):void{global $pdo;$uid=$_SESSION['user']['id']??null;$pdo->prepare('INSERT INTO audit_log(user_id,action,entity,entity_id,details) VALUES(?,?,?,?,?)')->execute([$uid,$action,$entity,$entityId,$details]);}
function mail_settings():array{$file=dirname(__DIR__,2).'/private/newsiga-mail.php';if(!is_file($file))throw new RuntimeException('O envio de e-mail ainda não foi configurado.');$settings=require $file;if(!is_array($settings))throw new RuntimeException('Configuração de e-mail inválida.');return $settings;}
function client_ip():string{return substr($_SERVER['REMOTE_ADDR']??'unknown',0,45);}
