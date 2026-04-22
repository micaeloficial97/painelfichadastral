<?php
require __DIR__.'/db.php';
require __DIR__.'/auth.php';
require_login();

$id     = (int)($_GET['id']   ?? 0);   // pre_id (id do cadastro)
$fileId = (int)($_GET['file'] ?? 0);
if ($id <= 0 || $fileId <= 0) { http_response_code(400); exit('Parâmetros inválidos'); }

define('PANEL_ROOT', realpath(dirname(__DIR__)));
define('UPLOADS_ROOT', PANEL_ROOT . '/uploads');

$st = $pdo->prepare("
  SELECT pre_id, orig_name, stored_name, COALESCE(mime,'') AS mime, COALESCE(size,0) AS size
  FROM submission_files
  WHERE id = ? AND pre_id = ?
  LIMIT 1
");
$st->execute([$fileId, $id]);
$f = $st->fetch(PDO::FETCH_ASSOC);
if (!$f) { http_response_code(404); exit('Arquivo não encontrado'); }

/* <<< NOVO: buscar CNPJ “limpo” para montar a pasta >>> */
$stDir = $pdo->prepare("
  SELECT REPLACE(REPLACE(REPLACE(cnpj,'.',''),'-',''),'/','') AS dir_key
  FROM pre_cadastro
  WHERE id = ?
  LIMIT 1
");
$stDir->execute([$f['pre_id']]);
$dirKey = $stDir->fetchColumn();
/*$ok = 
if($ok){
  echo json_encode(UPLOADS_ROOT . '/' . $dirKey . '/' . $f['stored_name']);
}*/
if (!$dirKey) { http_response_code(404); exit('Pasta do cadastro não encontrada'); }

/* Caminho físico baseado no CNPJ (dirKey) */
$path = UPLOADS_ROOT . '/' . $dirKey . '/' . $f['stored_name'];
if (!is_file($path)) { http_response_code(404); exit('Arquivo ausente'); }

$mime = strtolower($f['mime'] ?: (@mime_content_type($path) ?: 'application/octet-stream'));
$allowInline = ['application/pdf','image/jpeg','image/jpg','image/png','image/webp','image/gif','image/svg+xml','image/heic'];
$fname = basename($f['orig_name'] ?: $f['stored_name']);

/* >>> Zere QUALQUER saída anterior e desative compressão */
while (ob_get_level()) { ob_end_clean(); }
if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', '1'); }
@ini_set('zlib.output_compression', '0');

/* Headers mínimos e corretos */
header('X-Content-Type-Options: nosniff');
header('Content-Type: '.$mime);
header("Content-Disposition: inline; filename=\"{$fname}\"; filename*=UTF-8''".rawurlencode($fname));

/* NÃO envie Content-Length (evita quebra com gzip/proxy) */
$ok = @readfile($path);
if ($ok === false) { http_response_code(500); }
exit;