<?php
declare(strict_types=1);
require __DIR__.'/db.php';
require __DIR__.'/auth.php';
require_login();

$role = strtolower($_SESSION['role'] ?? '');
if (!in_array($role, ['admin','comercial','financeiro'], true)) { http_response_code(403); exit('Acesso negado.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método inválido.'); }

$preId = (int)($_POST['submission_id'] ?? 0);  // id do cadastro
$oldId = (int)($_POST['old_file_id'] ?? 0);
$note  = trim($_POST['note'] ?? '');

if ($preId <= 0) { http_response_code(400); exit('ID do cadastro ausente'); }
if ($oldId <= 0) { http_response_code(400); exit('ID do arquivo ausente'); }
if (!isset($_FILES['newfile']) || $_FILES['newfile']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400); exit('Falha no upload (code '.($_FILES['newfile']['error'] ?? -1).')');
}

// 1) Busca o registro antigo (para pegar o stored_name)
$st = $pdo->prepare("SELECT id, pre_id, orig_name, stored_name FROM submission_files WHERE id=? AND pre_id=? LIMIT 1");
$st->execute([$oldId, $preId]);
$old = $st->fetch(PDO::FETCH_ASSOC);
if (!$old) { http_response_code(404); exit('Arquivo não encontrado.'); }

// 2) Resolve pasta por CNPJ (pasta = CNPJ sem máscara)
$stC = $pdo->prepare("SELECT REPLACE(REPLACE(REPLACE(cnpj,'.',''),'-',''),'/','') AS dir_key FROM pre_cadastro WHERE id=? LIMIT 1");
$stC->execute([$preId]);
$dirKey = $stC->fetchColumn();
if (!$dirKey) { http_response_code(404); exit('Cadastro sem CNPJ para pasta.'); }

define('PANEL_ROOT', realpath(__DIR__));
define('UPLOADS_ROOT', dirname(PANEL_ROOT).'/uploads');
$destDir = UPLOADS_ROOT.'/'.$dirKey;
if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }

// 3) Valida tipo/tamanho e define nomes
$f     = $_FILES['newfile'];
$mime  = strtolower(@mime_content_type($f['tmp_name']) ?: 'application/octet-stream');
$okMimes = ['application/pdf','image/jpeg','image/jpg','image/png','image/webp','image/gif','image/svg+xml','image/heic'];
if (!in_array($mime, $okMimes, true)) { http_response_code(415); exit('Tipo de arquivo não permitido.'); }
if ($f['size'] > 20*1024*1024) { http_response_code(413); exit('Arquivo muito grande (máx. 20MB).'); }

$origName   = basename($f['name']);              // o nome que o usuário enviou (ex.: RG.pdf)
$storedName = $old['stored_name'];               // **mantém** o nome no disco
$fullPath   = $destDir.'/'.$storedName;

// 4) Substitui o binário (overwrite)
while (ob_get_level()) { ob_end_clean(); }       // evita travas
if (!@move_uploaded_file($f['tmp_name'], $fullPath)) {
  http_response_code(500); exit('Erro ao salvar arquivo no destino.');
}

// 5) Atualiza a mesma linha no BD (sem apagar/insert)
$stU = $pdo->prepare("UPDATE submission_files
                      SET orig_name=?, mime=?, size=?, uploaded_at=NOW()
                      WHERE id=? AND pre_id=?");
$stU->execute([$origName, $mime, (int)$f['size'], $oldId, $preId]);

// (Opcional) anexa observação
if ($note !== '') {
  try {
    $me  = current_user();
    $who = $me['email'] ?? $me['name'] ?? 'user';
    $append = "\n[".date('Y-m-d H:i')." {$who}] Substituição de arquivo: ".$note;
    $pdo->prepare("UPDATE pre_cadastro SET obs = CONCAT(COALESCE(obs,''), ?) WHERE id=?")->execute([$append, $preId]);
  } catch (\Throwable $e) { /* ignore */ }
}

// volta para os anexos
header('Location: /painel/index.php');
exit;
