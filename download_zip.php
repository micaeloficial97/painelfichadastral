<?php
require __DIR__.'/db.php';
require __DIR__.'/auth.php';
require_login();

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin','gestor','analista','comercial','financeiro'], true)) {
  http_response_code(403);
  exit('Acesso negado.');
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); exit('Parâmetros inválidos'); }

$st = $pdo->prepare("SELECT razao_social, cnpj FROM submissions WHERE id=?");
$st->execute([$id]);
$sub = $st->fetch();
if (!$sub) { http_response_code(404); exit('Cadastro não encontrado'); }

$onlyActive = empty($_GET['all']); // padrão: só ativos
if ($onlyActive) {
  $st2 = $pdo->prepare("SELECT file_name, file_path FROM submission_files WHERE submission_id=? AND (is_active=1 OR is_active IS NULL)");
} else {
  $st2 = $pdo->prepare("SELECT file_name, file_path FROM submission_files WHERE submission_id=?");
}
$st2->execute([$id]);
$files = $st2->fetchAll();
if (!$files) { http_response_code(404); exit('Sem arquivos'); }

$zipName = sprintf('Cadastro_%d_%s.zip', $id, date('YmdHis'));
$tmp = tempnam(sys_get_temp_dir(), 'zip');
$zip = new ZipArchive();
$zip->open($tmp, ZipArchive::OVERWRITE);
foreach ($files as $f) {
  if (is_file($f['file_path'])) {
    $zip->addFile($f['file_path'], $f['file_name']);
  }
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="'.$zipName.'"');
header('Content-Length: '.filesize($tmp));
readfile($tmp);
unlink($tmp);
exit;
