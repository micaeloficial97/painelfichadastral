<?php
// /painel/auth.php
session_start();

function require_login() {
  if (!isset($_SESSION['admin_id'])) {
    header('Location: /painel/login.php'); exit;
  }
}

function current_user() {
  return [
    'id'   => $_SESSION['admin_id'] ?? null,
    'name' => $_SESSION['name']     ?? 'Usuário',
    'role' => $_SESSION['role']     ?? 'comercial',
  ];
}

function require_role($roles) {
  $role = $_SESSION['role'] ?? '';
  $roles = is_array($roles) ? $roles : [$roles];
  if ($role === 'admin') return;
  if (!in_array($role, $roles, true)) {
    http_response_code(403);
    exit('Acesso negado.');
  }
}
