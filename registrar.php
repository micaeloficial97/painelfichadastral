<?php
// /painel/registrar.php
require __DIR__ . '/db.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';
  $role = trim($_POST['role'] ?? '');
try {
  $st = $pdo->prepare("SELECT id FROM admin_users WHERE email = ?");
  $st->execute([$email]);
  if ($st->fetchColumn()) {
    echo "Usuário já existe.\n";
    exit;
  }
  $hash = password_hash($senha, PASSWORD_DEFAULT);
  $pdo->prepare("INSERT INTO admin_users (nome, email, senha_hash, role, ativo) VALUES (?, ?, ?, ?, 1)")
      ->execute([$nome, $email, $hash, $role]);
  echo "<script>alert('Usuário criado com sucesso.');</script>";
  header('Location: /painel/login.php'); exit;
} catch (Throwable $e) {
  echo "Erro: " . $e->getMessage();
} 
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Cadastro — Painel</title>
  <link rel="stylesheet" href="/painel/assets/style.css">
  <style>
    .login-card{max-width:360px;margin:8vh auto;padding:24px;background:#fff;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
    .login-card h1{margin:0 0 12px}
    .login-card label{display:block;margin:10px 0 4px}
    .login-card input{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px}
    .login-card button{width:100%;margin-top:14px;padding:10px;border:0;border-radius:8px;background:#0b5cff;color:#fff;cursor:pointer}
    .error{color:#b30000;margin:8px 0}
  </style>
</head>
<body>
  <div class="login-card">
    <h1>Cadastrar</h1>
    <form method="post" autocomplete="off">
      <label for="nome">Nome</label>
      <input id="nome" name="nome" type="text" required>

      <label for="email">E-mail</label>
      <input id="email" name="email" type="email" required>

      <label for="senha">Senha</label>
      <input id="senha" name="senha" type="password" required>
      <label for="role">Setor</label>
      <select name="role" id="role" required>
        <option value="dono">Diretor</option>
        <option value="comercial">Comercial</option>
        <option value="financeiro">Financeiro</option>
      </select>

      <button type="submit">Cadastrar</button>
    </form>
   
  </div>
</body>
</html>