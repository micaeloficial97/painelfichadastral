<?php
// /painel/api/submissions/details.php
header('Content-Type: application/json; charset=UTF-8');
require __DIR__ . '/../../db.php';
require __DIR__ . '/../../auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'msg'=>'ID inválido']);
  exit;
}

$role = $_SESSION['role'] ?? 'comercial';
$canDownload = in_array($role, ['admin','comercial','financeiro'], true);
$canReplace  = in_array($role, ['admin','comercial'], true);

// === primeiro busca o registro ===
$st = $pdo->prepare("
  SELECT
    p.id AS pre_id,
    p.razaosocial, p.nomefantasia, p.cnpj, p.telefone, p.email,
    p.tipodecadastro, p.respostatipodecadastro, p.inscricaoestadual, p.dataconstituicao,
    p.logradouro, p.numero, p.bairro, p.cidade, p.estado, p.cep,
    p.transportadora, p.atuacao, p.respostaatuacao, p.comercializatoldos, p.fornecedordetoldos,
    p.possuiequipe, p.pessoasdaequipe, p.possuishowroom, p.fornecedordosohwroom,
    p.possuilojafisica, p.instalador, p.siteouinstagram, p.faturamentomensal,
    p.principalproduto, p.principalfornecedor, p.motivodaparceria,
    p.nomedopropietario, p.cpfdoproprietario, p.datanascimentodoproprietario,
    p.quantidadedesocio,
    m.id AS meta_id,
    m.consultor AS m_consultor,
    m.condicao_vendas AS m_condicao_vendas,
    m.status AS m_status,
    m.checklist_json, m.checklist_by, m.checklist_at
  FROM pre_cadastro p
  LEFT JOIN admin_submissions_meta m ON m.pre_id = p.id
  WHERE p.id = ?
");
$st->execute([$id]);
$p = $st->fetch(PDO::FETCH_ASSOC);

if (!$p) {
  http_response_code(404);
  echo json_encode(['ok'=>false,'msg'=>'Registro não encontrado']);
  exit;
}

if (!$p){ http_response_code(404); echo json_encode(['ok'=>false,'msg'=>'Registro não encontrado']); exit; }

$endereco = trim(($p['logradouro'] ?? '').' '.($p['numero'] ?? '').', '.($p['bairro'] ?? '').', '.($p['cidade'] ?? '').' - '.($p['estado'] ?? '').' — Cep.: '.($p['cep'] ?? ''));
$data_const = $p['dataconstituicao'] ?? '';
if ($data_const && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data_const) === 0 && preg_match('/^\d{4}-\d{2}-\d{2}/',$data_const)) {
  [$Y,$m,$d] = explode('-', substr($data_const,0,10));
  $data_const = "$d/$m/$Y";
}
$ck = null;
if (!empty($p['checklist_json'])) {
  $tmp = json_decode($p['checklist_json'], true);
  if (is_array($tmp)) $ck = $tmp;
}




$out = [
  'id' => (int)$p['pre_id'],
  'razao_social' => $p['razaosocial'] ?? '',
  'nome_fantasia' => $p['nomefantasia'] ?? '',
  'cnpj' => $p['cnpj'] ?? '',
  'telefone' => $p['telefone'] ?? '',
  'email' => $p['email'] ?? '',
  'tipo_cadastro' => $p['tipodecadastro'] ?? '',
  'resposta_tipo_cadastro' => $p['respostatipodecadastro'] ?? '',
  'inscricao_estadual' => $p['inscricaoestadual'] ?? '',
  'data_constituicao_br' => $data_const,
  'endereco_full' => $endereco,
  'transportadora' => $p['transportadora'] ?? '',
  'ramo_flag' => $p['atuacao'] ?? '',
  'ramo_det'  => $p['respostaatuacao'] ?? '',
  'toldo_flag' => $p['comercializatoldos'] ?? '',
  'toldo_qtd'  => $p['fornecedordetoldos'] ?? '',
  'equipe_flag' => $p['possuiequipe'] ?? '',                 // sim|nao (ou 1/0)
  'equipe_qtd'  => $p['pessoasdaequipe'] ?? '',            // número
  'showroom_flag'   => $p['possuishowroom'] ?? '',
  'showroom_det'    => $p['fornecedordosohwroom'] ?? '',    // se tiver detalhe
  'loja_fisica' => $p['possuilojafisica'] ?? '',
  'instalador' => $p['instalador'] ?? '',
  'site_instagram' => $p['siteouinstagram'] ?? '',
  'faturamento_mensal' => $p['faturamentomensal'] ?? '',
  'principal_produto' => $p['principalproduto'] ?? '',
  'principal_fornecedor' => $p['principalfornecedor'] ?? '',
    'como_conheceu' => $p['motivodaparceria'] ?? '',
  'proprietario_nome' => $p['nomedopropietario'] ?? '',
  'proprietario_cpf'  => $p['cpfdoproprietario'] ?? '',
  'proprietario_nasc' => $p['datanascimentodoproprietario'] ?? '',
  'quantidade_socios' => $p['quantidadedesocio'] ?? '',
  'consultor'        => $p['m_consultor'] ?? $p['consultor'] ?? '',
  'condicao_vendas'  => $p['m_condicao_vendas'] ?? $p['condicao_vendas'] ?? '',
  'status'           => $p['status'] ?? 'pendente',
  'checklist'        => $ck,
  'checklist_by'     => $p['checklist_by'] ?? null,
  'checklist_at'     => $p['checklist_at'] ?? null,
  'anexos' => [],                            // opcional: integrar paths depois
  'zip_download' => null
];
// -------- Anexos do cadastro --------
$anexos = [];
$stF = $pdo->prepare("
  SELECT id, orig_name, stored_name, mime, size, uploaded_at
  FROM submission_files
 WHERE pre_id = ?
                        ORDER BY uploaded_at DESC, id DESC
                        LIMIT 200");
$stF->execute([$id]);
foreach ($stF as $f) {
  $fid = (int)$f['id'];
  $anexos[] = [
    'id'          => $fid,
    'nome'        => $f['stored_name'],
    'mime'        => $f['mime'],
    'size'        => (int)($f['size'] ?? 0),
    'uploaded_at' => $f['uploaded_at'],
   // Permite o Dono ver a prévia, mas não baixar/substituir:
  'url_view'     => "/painel/preview.php?id={$id}&file={$fid}",
  'url_download' => $canDownload ? "/painel/download.php?id={$id}&file={$fid}" : null,
  'url_replace'  => $canReplace  ? true : false, // o painel.js só mostra botão se for true
  ];
}

$out['anexos']       = $anexos;
$out['zip_download'] = $canDownload ? "/painel/download_zip.php?id={$id}" : null;


echo json_encode(['ok'=>true,'data'=>$out], JSON_UNESCAPED_UNICODE);
