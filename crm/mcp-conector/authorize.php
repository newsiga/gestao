<?php
/**
 * Tela de autorização do OAuth, em /oauth/authorize. O Claude abre esta
 * página no navegador (ou no app) na hora de conectar; o Felipe entra com
 * o mesmo usuário e senha do CRM e autoriza. Se já estiver logado no CRM
 * neste navegador, basta confirmar.
 *
 * Erros no client_id ou na redirect_uri aparecem na própria página (nunca
 * redirecionam, para não mandar o navegador a um endereço não confiável).
 * Os demais voltam para o cliente como ?error=..., como manda o OAuth.
 */

require_once __DIR__ . '/../auth.php'; // sessão do CRM: esta_logado()
define('MCP_CONECTOR', true);
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/oauth.php';

const LOGIN_LIMITE_FALHAS_IP_15MIN = 5;
const LOGIN_LIMITE_FALHAS_TOTAL_HORA = 20;

header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: frame-ancestors 'none'");
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

if (!mcp_disponivel()) {
    pagina_erro('O conector está desativado no momento.');
}

$fonte = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$req = [];
foreach (['response_type', 'client_id', 'redirect_uri', 'state', 'code_challenge', 'code_challenge_method', 'resource', 'scope'] as $campo) {
    $req[$campo] = is_string($fonte[$campo] ?? null) ? $fonte[$campo] : '';
}

// ---- Cliente e redirect_uri: se falharem, o erro fica nesta página
$cliente = oauth_buscar_cliente($req['client_id']);
if (!$cliente) {
    pagina_erro('Cliente desconhecido. Remova o conector no Claude e adicione de novo.');
}
if ($req['redirect_uri'] === '' && count($cliente['redirect_uris']) === 1) {
    $req['redirect_uri'] = $cliente['redirect_uris'][0];
}
if (!in_array($req['redirect_uri'], $cliente['redirect_uris'], true)) {
    pagina_erro('Endereço de retorno não registrado para este cliente.');
}

// ---- Demais parâmetros: o erro volta para o cliente
$voltar = fn(string $erro, string $descricao) => redirecionar(oauth_url_retorno($req['redirect_uri'], [
    'error' => $erro, 'error_description' => $descricao, 'state' => $req['state'],
]));
if ($req['response_type'] !== 'code') {
    $voltar('unsupported_response_type', 'Só response_type=code é suportado.');
}
if ($req['code_challenge'] === '' || $req['code_challenge_method'] !== 'S256' || !preg_match('/^[A-Za-z0-9\-_]{43,128}$/', $req['code_challenge'])) {
    $voltar('invalid_request', 'PKCE com S256 é obrigatório.');
}
if (!oauth_recurso_valido($req['resource'])) {
    $voltar('invalid_target', 'resource deve ser ' . mcp_recurso());
}

$erroLogin = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['mcp_csrf']) || !hash_equals($_SESSION['mcp_csrf'], (string) ($_POST['csrf'] ?? ''))) {
        pagina_erro('A página expirou. Volte ao Claude e tente conectar de novo.');
    }

    if (($_POST['decisao'] ?? '') !== 'autorizar') {
        mcp_log('autorizacao', 'negado', ['client_id' => $cliente['client_id'], 'detalhe' => 'recusado pelo usuário']);
        $voltar('access_denied', 'Acesso recusado.');
    }

    $autenticado = esta_logado();
    if (!$autenticado) {
        $bloqueado = mcp_contar_eventos('login_falha', 'ip', mcp_ip(), 900) >= LOGIN_LIMITE_FALHAS_IP_15MIN
            || mcp_contar_eventos('login_falha', 'todos', null, 3600) >= LOGIN_LIMITE_FALHAS_TOTAL_HORA;
        if ($bloqueado) {
            $erroLogin = 'Muitas tentativas erradas. Aguarde 15 minutos e tente de novo.';
        } else {
            $usuario = trim((string) ($_POST['usuario'] ?? ''));
            $senha = (string) ($_POST['senha'] ?? '');
            $autenticado = defined('CRM_LOGIN_USUARIO') && defined('CRM_LOGIN_SENHA_HASH')
                && hash_equals(CRM_LOGIN_USUARIO, $usuario)
                && password_verify($senha, CRM_LOGIN_SENHA_HASH);
            if (!$autenticado) {
                mcp_log('login_falha', 'negado', ['client_id' => $cliente['client_id']]);
                $erroLogin = 'Usuário ou senha incorretos.';
            }
        }
    }

    if ($autenticado) {
        unset($_SESSION['mcp_csrf']);
        $codigo = oauth_criar_codigo($cliente['client_id'], $req['redirect_uri'], $req['code_challenge']);
        mcp_log('autorizacao', 'ok', ['client_id' => $cliente['client_id'], 'detalhe' => $cliente['nome']]);
        redirecionar(oauth_url_retorno($req['redirect_uri'], ['code' => $codigo, 'state' => $req['state']]));
    }
}

$_SESSION['mcp_csrf'] = $_SESSION['mcp_csrf'] ?? bin2hex(random_bytes(32));
$logado = esta_logado();
$destino = parse_url($req['redirect_uri'], PHP_URL_HOST);
$h = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

function redirecionar(string $url): never
{
    header('Location: ' . $url, true, 302);
    exit;
}

function pagina_erro(string $mensagem): never
{
    http_response_code(400);
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>CRM Newsiga — Conector</title></head><body style="font-family:system-ui,sans-serif;background:#f3f2ec;color:#0d2b22;padding:40px 16px;">'
        . '<div style="max-width:420px;margin:0 auto;background:#fff;border:1px solid #d9d8d1;border-radius:12px;padding:32px;">'
        . '<h1 style="font-size:18px;margin:0 0 12px;">Não foi possível autorizar</h1><p style="margin:0;font-size:14.5px;">'
        . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . '</p></div></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Autorizar conector</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --cream:#f3f2ec; --forest:#0d2b22; --muted:#6b7770; --border:#d9d8d1; --white:#ffffff; --red:#c0503e;
    --font-display:'Manrope', system-ui, sans-serif;
    --font-ui:'Inter', system-ui, sans-serif;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  body{background:var(--cream); color:var(--forest); font-family:var(--font-ui); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px 16px;}
  .caixa{background:var(--white); border:1px solid var(--border); border-radius:12px; padding:36px; width:100%; max-width:400px;}
  .logo{font-family:var(--font-display); font-weight:800; font-size:20px; margin-bottom:18px;}
  .logo span{color:var(--muted); font-weight:600; font-size:14px;}
  h1{font-family:var(--font-display); font-size:17px; font-weight:700; margin-bottom:10px;}
  .texto{font-size:14px; line-height:1.5; margin-bottom:8px;}
  .lista{font-size:13.5px; color:var(--muted); line-height:1.6; margin:4px 0 22px 18px;}
  .field{margin-bottom:14px;}
  .field label{display:block; font-size:13px; font-weight:600; margin-bottom:6px;}
  .field input{width:100%; height:44px; border-radius:8px; border:1px solid var(--border); padding:0 14px; font-size:14.5px; font-family:var(--font-ui);}
  .field input:focus{outline:none; border-color:var(--forest);}
  .botoes{display:flex; flex-direction:row-reverse; gap:10px; margin-top:8px;}
  .btn{flex:1; height:46px; border-radius:8px; font-weight:700; font-size:14.5px; cursor:pointer; font-family:var(--font-ui);}
  .btn-sim{background:var(--forest); color:var(--white); border:none;}
  .btn-nao{background:var(--white); color:var(--forest); border:1px solid var(--border);}
  .erro{background:#f1d9d4; color:var(--red); padding:12px 14px; border-radius:8px; font-size:13.5px; margin-bottom:16px;}
  .nota{font-size:12.5px; color:var(--muted); margin-top:16px;}
</style>
</head>
<body>
  <div class="caixa">
    <div class="logo">crm<span>.newsiga</span></div>
    <h1>Autorizar acesso ao fluxo de caixa</h1>
    <p class="texto"><strong><?= $h($cliente['nome']) ?></strong> quer consultar o CRM em seu nome:</p>
    <ul class="lista">
      <li>receitas, cobranças e status no ASAAS;</li>
      <li>despesas com fornecedores;</li>
      <li>somente leitura: nada é criado nem alterado.</li>
    </ul>
    <?php if ($erroLogin): ?><div class="erro"><?= $h($erroLogin) ?></div><?php endif; ?>
    <form method="POST" action="/oauth/authorize">
      <?php foreach ($req as $campo => $valor): ?>
        <input type="hidden" name="<?= $h($campo) ?>" value="<?= $h($valor) ?>">
      <?php endforeach; ?>
      <input type="hidden" name="csrf" value="<?= $h($_SESSION['mcp_csrf']) ?>">
      <?php if (!$logado): ?>
        <div class="field">
          <label for="usuario">Usuário do CRM</label>
          <input type="text" id="usuario" name="usuario" autocomplete="username" autofocus>
        </div>
        <div class="field">
          <label for="senha">Senha</label>
          <input type="password" id="senha" name="senha" autocomplete="current-password">
        </div>
      <?php endif; ?>
      <div class="botoes">
        <?php /* Autorizar vem primeiro no HTML: é o botão que o Enter aciona. row-reverse põe Recusar à esquerda. */ ?>
        <button type="submit" name="decisao" value="autorizar" class="btn btn-sim">Autorizar</button>
        <button type="submit" name="decisao" value="negar" class="btn btn-nao">Recusar</button>
      </div>
    </form>
    <p class="nota">Depois de autorizar, você volta para <?= $h((string) $destino) ?>.</p>
  </div>
</body>
</html>
