<?php
// /painel/login.php
require __DIR__.'/db.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';

  try {
    $st = $pdo->prepare("SELECT id, nome, email, senha_hash, role, ativo FROM admin_users WHERE email = ?");
    $st->execute([$email]);
    $u = $st->fetch(PDO::FETCH_ASSOC);

    if (!$u || !$u['ativo'] || !password_verify($senha, $u['senha_hash'])) {
      $error = 'Credenciais inválidas.';
    } else {
      $_SESSION['admin_id'] = (int)$u['id'];
      $_SESSION['name']     = $u['nome'];
      $_SESSION['role']     = $u['role'];
      header('Location: /painel/index.php'); exit;
    }
  } catch (Throwable $e) {
    $error = 'Falha ao autenticar. Tente novamente.';
  }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Login — Painel</title>
  <link rel="stylesheet" href="/painel/assets/style.css">
  <style>
    .login-card{max-width:360px;
      margin:8vh auto;
      padding:24px;
      background:#fff;
      border-radius:12px;
      box-shadow:0 10px 30px rgba(0,0,0,.08)}
    .login-card h1{margin:0 0 12px}
    .login-card label{display:block;margin:10px 0 4px}
    .login-card input{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px}
    .login-card button{width:100%;margin-top:14px;padding:10px;border:0;border-radius:8px;background:#0b5cff;color:#fff;cursor:pointer}
    .error{color:#b30000;margin:8px 0}
  </style>
</head>
<body>
  <div class="login-card">
    <h1>Entrar</h1>
    <?php if ($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <label for="email">E-mail</label>
      <input id="email" name="email" type="email" required>

      <label for="senha">Senha</label>
      <input id="senha" name="senha" type="password" required>

      <button type="submit">Acessar</button>
    </form>
   
  </div>
</body>
</html>
