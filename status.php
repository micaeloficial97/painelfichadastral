<?php
require __DIR__.'/db.php';
require __DIR__.'/auth.php';
require_login();

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$notes = trim($_POST['notes'] ?? '');
$valid = ['Analise_Comercial','Analise_Financeiro','Reprovado_Financeiro', 'Cadastrado'];
if (!$id || !in_array($status,$valid,true)) { http_response_code(400); exit('Requisição inválida'); }

$me = current_user();
$st = $pdo->prepare("UPDATE submissions 
  SET status=?, review_notes=?, reviewed_by=?, reviewed_at=NOW()
  WHERE id=?");
$st->execute([$status, $notes, $me['email'], $id]);

header('Location: /painel/index.php?view='.$id);
