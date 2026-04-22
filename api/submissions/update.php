<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', '0');           // não imprimir warnings na resposta
error_reporting(E_ALL);                   // (logar no error_log se precisar)

require __DIR__ . '/../../db.php';
session_start();

// Leia o corpo JSON
$payload = json_decode(file_get_contents('php://input'), true) ?? [];

// Campos
$id        = isset($payload['id']) ? (int)$payload['id'] : 0;
$status    = isset($payload['status']) ? trim($payload['status']) : null;
$obs       = trim($payload['obs'] ?? '');
$consultor = trim($payload['consultor'] ?? '');
$cond      = trim($payload['condicao_vendas'] ?? '');
$ck        = $payload['checklist'] ?? null;
$old       = $payload['old_status'] ?? null;
$newStatus = $payload['new_status'] ?? null;

// VALIDA ID
if ($id <= 0) {
  http_response_code(422);
  echo json_encode(['ok'=>false, 'msg'=>'ID inválido']);
  exit;
}

// if ($status === 'Analise_Financeiro') {
//   if (!is_array($ck)) {
//     http_response_code(422);
//     echo json_encode(['ok'=>false,'msg'=>'Checklist obrigatório para aprovar.']);
//     exit;
//   }
  // validação dos 4 anexos obrigatórios, etc...

  // garanta que o PDO está em modo exceção (se no db.php ainda não estiver)
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($id <= 0) { http_response_code(422); echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

$role = $_SESSION['role'] ?? '';
$uid  = (int)($_SESSION['admin_id'] ?? 0);

// Dono é somente leitura
if ($role === 'dono') {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'Acesso negado: usuário apenas leitura.']); exit;
}

try {
  $pdo->beginTransaction();

  // garante que o pre_cadastro existe
  $st0 = $pdo->prepare('SELECT id FROM pre_cadastro WHERE id=? FOR UPDATE');
  $st0->execute([$id]);
  if (!$st0->fetchColumn()) { throw new RuntimeException('Registro não encontrado'); }

  // lê status anterior (se houver) na meta
$st1 = $pdo->prepare('SELECT status FROM admin_submissions_meta WHERE pre_id=?');
$st1->execute([$id]);
$old = $st1->fetchColumn() ?: 'Analise_Comercial';

// ––––––––– Checklist: normalização e checagem quando for aprovar comercial –––––––––
// -------- Checklist: normalização e checagem quando for aprovar comercial --------
$mustCheckChecklist = ($role === 'comercial' && $status === 'Analise_Financeiro');
if ($mustCheckChecklist) {
  $checklist = $payload['checklist'] ?? null;
  if (!is_array($checklist)) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'msg'=>'Checklist obrigatório para aprovar.']);
    $pdo->rollBack(); exit;
  }

  // --- NORMALIZAÇÃO ---
  // Se vier booleano, converte para ['ok'=>bool]. Garante campos esperados.
  foreach (['contrato','socios','endereco'] as $sec) {
    if (!isset($checklist[$sec])) $checklist[$sec] = ['ok'=>false];
    if (is_bool($checklist[$sec])) {
      $checklist[$sec] = ['ok'=>$checklist[$sec]];
    } elseif (!is_array($checklist[$sec])) {
      $checklist[$sec] = ['ok'=>false];
    } elseif (!array_key_exists('ok', $checklist[$sec])) {
      $checklist[$sec]['ok'] = false;
    } else {
      $checklist[$sec]['ok'] = (bool)$checklist[$sec]['ok'];
    }
  }

  if (!isset($checklist['anexos']) || !is_array($checklist['anexos'])) {
    $checklist['anexos'] = [];
  }
  foreach (['loja_interna','fachada','comprovante_endereco','cartao_cnpj'] as $k) {
    $v = $checklist['anexos'][$k] ?? false;
    if (is_array($v)) {
      // aceita tanto ['ok'=>true] quanto true/false puro
      $ok = isset($v['ok']) ? (bool)$v['ok'] : (bool)$v;
    } else {
      $ok = (bool)$v;
    }
    $checklist['anexos'][$k] = ['ok'=>$ok];
  }
  // se quiser, compute um "ok" geral de anexos (opcional)
  $checklist['anexos']['ok'] =
      $checklist['anexos']['loja_interna']['ok'] &&
      $checklist['anexos']['fachada']['ok'] &&
      $checklist['anexos']['comprovante_endereco']['ok'] &&
      $checklist['anexos']['cartao_cnpj']['ok'];

  // --- VALIDAÇÃO ---
  $need = [
    'contrato', 'socios', 'endereco',
    'anexos.loja_interna', 'anexos.fachada', 'anexos.comprovante_endereco', 'anexos.cartao_cnpj'
  ];
  $bad = [];
  foreach ($need as $path) {
    $parts = explode('.', $path);
    $node = $checklist;
    foreach ($parts as $p) {
      if (!isset($node[$p])) { $bad[]=$path; $node=null; break; }
      $node = $node[$p];
    }
    if ($node === null) continue;

    // aceita booleano ou array com 'ok'
    if (is_bool($node)) {
      if (!$node) $bad[]=$path;
    } elseif (is_array($node)) {
      if (empty($node['ok'])) $bad[]=$path;
    } else {
      $bad[]=$path;
    }
  }
  if ($bad) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'msg'=>'Checklist incompleto: '.implode(', ', $bad)]);
    $pdo->rollBack(); exit;
  }

  // opcional: guardar checklist normalizado de volta no $payload
  //$payload['checklist'] = $checklist;
}



// Bloqueio de transições por papel (matriz de estados)
if ($status !== null) {

   if ($status === $old) {
    http_response_code(409);
    echo json_encode(['ok'=>false,'msg'=>'Este status já está definido. Nenhuma alteração realizada.']);
    $pdo->rollBack(); exit;
  }
  
  if ($role === 'comercial') {
    // pode aprovar se o status atual for Analise_Comercial OU Reprovado_Financeiro
    $allowed = ($status === 'Analise_Financeiro' && in_array($old, ['Analise_Comercial','Reprovado_Financeiro'], true));
    if (!$allowed) {
      http_response_code(409);
      echo json_encode(['ok'=>false,'msg'=>'Comercial só pode aprovar cadastros Analise_Comercials ou reprovados pelo Financeiro.']);
      $pdo->rollBack(); exit;
    }
  } elseif ($role === 'financeiro') {
    // só atua após aprovação do Comercial
    $allowed = ($old === 'Analise_Financeiro' && in_array($status, ['Reprovado_Financeiro','Cadastrado'], true));
    if (!$allowed) {
      http_response_code(409);
      echo json_encode(['ok'=>false,'msg'=>'Financeiro só pode atuar após aprovação do Comercial.']);
      $pdo->rollBack(); exit;
    }
  } // admin pode tudo, se quiser, aplique as mesmas regras
  // não repetir o mesmo status
 
}

  

  // Regras por papel
  $cadFlag = 0; // default para `financeiro_cadastrado`, evita NULL
  if ($role === 'comercial') {
    if ($status !== null && $status !== 'Analise_Financeiro') {
      http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'Ação não permitida ao Comercial']); $pdo->rollBack(); exit;
    }
    if ($status === 'Analise_Financeiro') {
      if ($consultor===''){ http_response_code(422); echo json_encode(['ok'=>false,'msg'=>'Selecione o consultor']); $pdo->rollBack(); exit; }
      if ($cond===''){ http_response_code(422); echo json_encode(['ok'=>false,'msg'=>'Selecione a condição de vendas']); $pdo->rollBack(); exit; }
    }
    $cadFlag = 0;
  }

  if ($role === 'financeiro') {
    if ($status === 'Reprovado_Financeiro' && $obs === '') {
      http_response_code(422); echo json_encode(['ok'=>false,'msg'=>'Informe o motivo da reprovação']); $pdo->rollBack(); exit;
    }
    // pode enviar status 'cadastrado' sem obs
  }
  if ($role === 'admin' && $status === 'Cadastrado') { 
    $old = 'Cadastrado';
    $status = 'Analise_Financeiro';
  }
  // Se financeiro marcou 'cadastrado', ativa flag
  if ($role === 'financeiro' && $status === 'Cadastrado') {
    $old = 'Analise_Financeiro';
    $status = 'Cadastrado';
    $cadFlag = 1; 
  }

  // valida status se veio
  if ($status !== null) {
    $valid = ['Analise_Comercial','Analise_Financeiro','Reprovado_Financeiro','Cadastrado'];
    if (!in_array($status, $valid, true)) {
      http_response_code(422); echo json_encode(['ok'=>false,'msg'=>'Status inválido']); $pdo->rollBack(); exit;
    }
  }

  // UPSERT na meta
$sql = "INSERT INTO admin_submissions_meta
          (pre_id, status, consultor, condicao_vendas, status_obs, status_by, status_at,
           financeiro_cadastrado, checklist_json, checklist_by, checklist_at)
        VALUES
          (:id, COALESCE(:s,'Analise_Comercial'), NULLIF(:cst,''), NULLIF(:cv,''), :obs, :byu, NOW(),
           :fc, :chk, :chkby, :chkat)
        ON DUPLICATE KEY UPDATE
          status               = COALESCE(VALUES(status), status),
          consultor            = COALESCE(VALUES(consultor), consultor),
          condicao_vendas      = COALESCE(VALUES(condicao_vendas), condicao_vendas),
          status_obs           = VALUES(status_obs),
          status_by            = VALUES(status_by),
          status_at            = VALUES(status_at),
          financeiro_cadastrado= GREATEST(financeiro_cadastrado, VALUES(financeiro_cadastrado)),
          checklist_json       = COALESCE(VALUES(checklist_json), checklist_json),
          checklist_by         = COALESCE(VALUES(checklist_by), checklist_by),
          checklist_at         = COALESCE(VALUES(checklist_at), checklist_at)";
$pdo->prepare($sql)->execute([
  ':id'=>$id, ':s'=>$status, ':cst'=>$consultor, ':cv'=>$cond,
  ':obs'=>$obs, ':byu'=>$uid ?: null, ':fc'=>$cadFlag,
  ':chk'=> $mustCheckChecklist ? json_encode($checklist, JSON_UNESCAPED_UNICODE) : null,
  ':chkby'=> $mustCheckChecklist ? ($uid ?: null) : null,
  ':chkat'=> $mustCheckChecklist ? date('Y-m-d H:i:s') : null,
]);


  // Histórico (só quando a ação exige)
 $acao = null;
if ($role === 'comercial'  && $status === 'Analise_Financeiro')   $acao = 'comercial_aprovou';
if ($role === 'financeiro' && $status === 'Reprovado_Financeiro') $acao = 'financeiro_reprovou';
if ($role === 'financeiro' && $status === 'Cadastrado')           $acao = 'financeiro_cadastrou';
if ($role === 'admin') {
  if ($status === 'Analise_Financeiro')   $acao = 'comercial_aprovou';
  if ($status === 'Analise_Comercial')             $acao = 'financeiro_reprovou';
  if ($status === 'Cadastrado')           $acao = 'financeiro_cadastrou';
}


if ($acao !== null /* e mudou */ && $status !== $old) {
  $pdo->prepare('INSERT INTO submissions_history (submission_id, acao, old_status, new_status,  obs, by_user) VALUES (?, ?, ?, ?, ?, ?)')
      ->execute([$id, $acao, $old, $status, $obs, $uid ?: null]);
}


  $pdo->commit();
  echo json_encode(['ok'=>true, 'new_status'=>$status ?? $old]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Falha ao atualizar: '. $e->getMessage()]);
}
