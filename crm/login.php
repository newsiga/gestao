<?php
require_once __DIR__ . '/auth.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (
        defined('CRM_LOGIN_USUARIO') && defined('CRM_LOGIN_SENHA_HASH')
        && hash_equals(CRM_LOGIN_USUARIO, $usuario)
        && password_verify($senha, CRM_LOGIN_SENHA_HASH)
    ) {
        session_regenerate_id(true);
        $_SESSION['crm_logado'] = true;
        $voltar = $_GET['voltar'] ?? 'crm-newsiga-painel.php';
        // Só aceita voltar pra um caminho local, nunca pra outro domínio
        if (!preg_match('#^/[a-zA-Z0-9_\-./?=&]*$#', $voltar)) {
            $voltar = '/crm-newsiga-painel.php';
        }
        header('Location: ' . $voltar);
        exit;
    }
    $erro = 'Usuário ou senha incorretos.';
}

if (esta_logado()) {
    header('Location: /crm-newsiga-painel.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM Newsiga — Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --cream:#f3f2ec; --forest:#0d2b22; --muted:#6b7770; --border:#d9d8d1; --white:#ffffff; --red:#c0503e;
    --font-display:'Manrope', system-ui, sans-serif;
    --font-ui:'Inter', system-ui, sans-serif;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  body{background:var(--cream); color:var(--forest); font-family:var(--font-ui); min-height:100vh; display:flex; align-items:center; justify-content:center;}
  .login-box{background:var(--white); border:1px solid var(--border); border-radius:12px; padding:40px; width:100%; max-width:360px;}
  .logo{font-family:var(--font-display); font-weight:800; font-size:20px; margin-bottom:6px;}
  .logo span{color:var(--muted); font-weight:600; font-size:14px;}
  .sub{color:var(--muted); font-size:13.5px; margin-bottom:28px;}
  .field{margin-bottom:16px;}
  .field label{display:block; font-size:13px; font-weight:600; margin-bottom:6px;}
  .field input{width:100%; height:44px; border-radius:8px; border:1px solid var(--border); padding:0 14px; font-size:14.5px; font-family:var(--font-ui);}
  .field input:focus{outline:none; border-color:var(--forest);}
  .btn{width:100%; height:46px; background:var(--forest); color:var(--white); border:none; border-radius:8px; font-weight:700; font-size:14.5px; cursor:pointer; margin-top:8px;}
  .erro{background:#f1d9d4; color:var(--red); padding:12px 14px; border-radius:8px; font-size:13.5px; margin-bottom:16px;}
</style>
</head>
<body>
  <div class="login-box">
    <div class="logo">crm<span>.newsiga</span></div>
    <div class="sub">Acesso restrito — uso interno.</div>
    <?php if ($erro): ?><div class="erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
    <form method="POST">
      <div class="field">
        <label>Usuário</label>
        <input type="text" name="usuario" required autofocus>
      </div>
      <div class="field">
        <label>Senha</label>
        <input type="password" name="senha" required>
      </div>
      <button type="submit" class="btn">Entrar</button>
    </form>
  </div>
</body>
</html>
