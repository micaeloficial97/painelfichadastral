<?php
require __DIR__.'/db.php';
require __DIR__.'/auth.php';
require_login();

$me = current_user();
$role = $_SESSION['role'] ?? 'comercial';
$body = ['page-fichas'];           // ativa a skin
$body[] = 'hide-zip';          // oculta o botão de baixar ZIP

// mapeia papel → classe de permissão
if ($role === 'dono')        $body[] = 'is-viewer';     // só ver (sem baixar/substituir/aprovar)
elseif ($role === 'financeiro') $body[] = 'is-finance'; // sem upload
elseif ($role === 'comercial')  $body[] = 'is-comercial';
elseif ($role === 'admin')       $body[] = 'is-admin';  // tudo liberado

$adminId = (int)($_SESSION['admin_id'] ?? 0);

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 20;
$offset = ($page-1)*$pageSize;

// filtros
$where  = " WHERE 1=1 ";
$params = [];

if ($q !== '') {
  $where .= " AND (p.cnpj LIKE ? OR p.razaosocial LIKE ? OR p.email LIKE ?) ";
  $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}

// filtro por status
// if ($status !== '') {
//   $where .= " AND COALESCE(m.status,'Analise_Comercial') = ? ";
//   $params[] = $status;
// }

if (!empty($_GET['status'])) {
  $statusList = $_GET['status']; // array
  // limpa valores vazios/sujos
  $statusList = array_filter($statusList, fn($v) => $v !== '');

  if (!empty($statusList)) {
    // ex: pc.status IN (?,?,?)
    $placeholders = implode(',', array_fill(0, count($statusList), '?'));
    $where .= "AND COALESCE(m.status, 'Analise_Comercial') IN ($placeholders)";
    $params = array_merge($params, $statusList);
  }
}


// COUNT (usa o MESMO $where)
$total = $pdo->prepare("SELECT COUNT(*) 
                        FROM pre_cadastro p
                        LEFT JOIN admin_submissions_meta m ON m.pre_id = p.id
                        $where");
$total->execute($params);
$totalRows = (int)$total->fetchColumn();

// SELECT (usa o MESMO $where)
$sql = "SELECT
          p.id, p.data_cadastro, p.razaosocial, p.cnpj, p.telefone, p.email,
          COALESCE(m.status,'Analise_Comercial') AS status,
          COALESCE(m.financeiro_cadastrado,0) AS financeiro_cadastrado
        FROM pre_cadastro p
        LEFT JOIN admin_submissions_meta m ON m.pre_id = p.id
        $where
        ORDER BY p.id DESC
        LIMIT $pageSize OFFSET $offset";
$st = $pdo->prepare($sql);
$st->execute($params);

$items = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Fichas Cadastrais — Painel</title>
  <link rel="stylesheet" href="/painel/assets/style.css?v=3">
  <link rel="stylesheet" href="/painel/assets/painel.css?v=3">
  <link rel="stylesheet" href="/painel/assets/skin-fichas.css?v=3">
</head>
<body class="page-fichas hide-zip">
<header>
  <h1>Fichas Cadastrais</h1>
  <div class="user">Olá, <?=htmlspecialchars($me['name'])?> | <a href="/painel/logout.php">Sair</a></div>
</header>

<form class="filters" method="get" id="filterForm">
  <input type="text" name="q" value="<?=htmlspecialchars($q)?>" placeholder="Buscar por CNPJ, Razão, e-mail">
  <!-- <select name="status">
    <option value="">Todos os status</option>
    <?php foreach (['Analise_Comercial','Analise_Financeiro','Reprovado_Financeiro', 'Cadastrado'] as $s): ?>
      <option value="<?=$s?>" <?=$status===$s?'selected':''?>><?=$s?></option>
    <?php endforeach; ?>
  </select> -->
      
        <div class="status-group">
    <?php
      // Lista de status disponíveis
      $statusOptions = [
        'Analise_Comercial',
        'Analise_Financeiro',
        'Reprovado_Financeiro',
        'Rejeitado_Comercial',
        'Cadastrado'
      ];

      // Garante que $status seja sempre array pra poder usar in_array sem erro
      $statusSelecionado = [];
      if (isset($status)) {
        // Se já vier como array (ex: status[]=Analise_Comercial&status[]=Cadastrado)
        if (is_array($status)) {
          $statusSelecionado = $status;
        } else if ($status !== '') {
          // Se vier como string antiga (ex: status=Analise_Comercial)
          $statusSelecionado = [$status];
        }
      }
      // Se nenhum status foi selecionado pelo usuario, aplica padrao por role
      if (empty($statusSelecionado)) {
        if ($role === 'comercial') {
          $statusSelecionado = ['Analise_Comercial','Reprovado_Financeiro','Rejeitado_Comercial'];
        } elseif ($role === 'financeiro') {
          $statusSelecionado = ['Analise_Financeiro'];
        } elseif ($role === 'admin' || $role === 'dono') {
          $statusSelecionado = [];
        }
      }
        foreach ($statusOptions as $s):
      
    ?>
    <?php if ($role === 'comercial'): 
       // padrao aplicado acima quando nao ha selecao do usuario
     ?>
      <label class="chk-inline">
        <input
          type="checkbox"
          name="status[]"
          value="<?= $s ?>"
          <?= in_array($s, $statusSelecionado, true) ? 'checked' : '' ?>
        >
        <span><?= $s ?></span>
      </label>
<?php endif; ?>

    <?php if ($role === 'financeiro'): 
       // padrao aplicado acima quando nao ha selecao do usuario
     ?>
      <label class="chk-inline">
        <input
          type="checkbox"
          name="status[]"
          value="<?= $s ?>"
          <?= in_array($s, $statusSelecionado, true) ? 'checked' : '' ?>
        >
        <span><?= $s ?></span>
      </label>
<?php endif; ?>
  <?php if ($role === 'admin' || $role === 'dono'): 
       // sem padrao fixo; respeita selecao do usuario
     ?>
      <label class="chk-inline">
        <input
          type="checkbox"
          name="status[]"
          value="<?= $s ?>"
          <?= in_array($s, $statusSelecionado, true) ? 'checked' : '' ?>
        >
        <span><?= $s ?></span>
      </label>
<?php endif; ?>
    <?php endforeach; ?>
  </div>

  <?php if ($role ==='comercial' || $role ==='financeiro'): ?>
  <script>
document.addEventListener('DOMContentLoaded', function () {
  const chave = 'autoSubmitJaRodou';
  const hasStatus = new URLSearchParams(location.search).has('status[]');

  // Se ainda não rodei nessa aba e não há status já no URL
  if (!sessionStorage.getItem(chave) && !hasStatus) {
    const form = document.getElementById('filterForm');
    if (form) {
      // Marca ANTES de enviar, pra não cair em loop
      sessionStorage.setItem(chave, 'true');
      form.submit();
    }
  }
});
</script>
  <?php endif; ?>  
  <button type="submit" class="btn">Filtrar</button>
  
</form>

<table class="table">
<thead>
  <tr>
    <th class="col-data">Data do envio</th>
    <th class="col-razao">Razão Social</th>
    <th>CNPJ</th>
    <th class="col-telefone">Telefone</th>
    <th>E-mail</th>
    <th class="col-status">Status</th>
    <th class="col-acoes">Ações</th>
  </tr>
</thead>

  <tbody>
  <?php foreach ($items as $r): ?>
    <tr class="data-row">
  <td><?=htmlspecialchars($r['data_cadastro'])?></td>
  <td><?=htmlspecialchars($r['razaosocial'])?></td>
  <td><?=htmlspecialchars($r['cnpj'])?></td>
  <td><?=htmlspecialchars($r['telefone'] ?? '')?></td>
  <td><?=htmlspecialchars($r['email'])?></td>

      <!-- STATUS -->
      <td><span class="badge <?=htmlspecialchars($r['status'])?>"><?=htmlspecialchars($r['status'])?></span></td>

      <!-- ANEXOS -->
      

      <!-- AÇÕES (regras por papel) -->
      <td class="actions">
        <?php if ($role === 'comercial' || $role === 'admin'): ?>
          <div class="row-actions">
            <!-- Preencha as opções depois -->
            <select id="consultor_<?= $r['id'] ?>">
              <option value="">Selecionar consultor...</option>
              <option value="Andreza">Andreza</option>
              <option value="Berenice">Berenice</option>
              <option value="Diretoria">Diretoria</option>   
              <option value="Edgar">Edgar</option>
              <option value="Edilson">Edilson</option>
              <option value="Emerson">Emerson</option>
              <option value="Kazza">Kazza</option>
              <option value="Wendel">Wendel</option>
              <option value="Wesley">Wesley</option>
            </select>
            <select id="cond_<?= $r['id'] ?>">
              <option value="">Condição de vendas...</option>
              <option value="07/14/21/28/35/42 dias">Boleto 28d</option>
              <option value="07/14/21/28/35/42 dias">07/14/21/28/35/42 dias</option>
              <option value="09 Parcelas">09 Parcelas</option>
              <option value="10 dias">10 dias</option>
              <option value="10 inico 60 dias">10 inico 60 dias</option>
              <option value="10 parcelas">10 parcelas</option>
              <option value="10 parcelas - carencia 6 meses">10 parcelas - carencia 6 meses</option>
              <option value="10/20 dias">10/20 dias</option>
              <option value="11 parcelas">11 parcelas</option>
              <option value="12 parcelas">12 parcelas</option>
              <option value="12 parcelas ( 7 em 7 dias )">12 parcelas ( 7 em 7 dias )</option>
              <option value="13 parcelas">13 parcelas</option>
              <option value="14 dias">14 dias</option>
              <option value="14 parcelas">14 parcelas</option>
              <option value="14/21 dias">14/21 dias</option>
              <option value="14/21/28 dias">14/21/28 dias</option>
              <option value="14/21/28/35 dias">14/21/28/35 dias</option>
              <option value="14/21/28/42 dias">14/21/28/42 dias</option>
              <option value="14/21/35 dias">14/21/35 dias</option>
              <option value="14/28">14/28</option>
              <option value="14/28/35 dias">14/28/35 dias</option>
              <option value="14/28/35/42 dias">14/28/35/42 dias</option>
              <option value="14/28/42 dias">14/28/42 dias</option>
              <option value="14/28/42/56 dias">14/28/42/56 dias</option>
              <option value="14/28/42/56/70/84 dias">14/28/42/56/70/84 dias</option>
              <option value="14/28/56 dias">14/28/56 dias</option>
              <option value="14/28/56/84 dias">14/28/56/84 dias</option>
              <option value="14/35 dias">14/35 dias</option>
              <option value="14/42 dias">14/42 dias</option>
              <option value="14/42/56 dias">14/42/56 dias</option>
              <option value="15 dias">15 dias</option>
              <option value="15 parcelas">15 parcelas</option>
              <option value="15/30/45 dias">15/30/45 dias</option>
              <option value="15/30/45/60/75 dias">15/30/45/60/75 dias</option>
              <option value="153 dias">153 dias</option>
              <option value="18 parcelas">18 parcelas</option>
              <option value="180 dias">180 dias</option>
              <option value="2 Parcelas">2 Parcelas</option>
              <option value="20 Dias">20 Dias</option>
              <option value="20 parcelas ( 28/28 dias )">20 parcelas ( 28/28 dias )</option>
              <option value="20/40 dias">20/40 dias</option>
              <option value="20/40/60 dias">20/40/60 dias</option>
              <option value="21 dias">21 dias</option>
              <option value="21/28/35 dias">21/28/35 dias</option>
              <option value="21/28/42 dias">21/28/42 dias</option>
              <option value="21/42 dias">21/42 dias</option>
              <option value="24 Parcelas">24 Parcelas</option>
              <option value="28 dias">28 dias</option>
              <option value="28/35 dias">28/35 dias</option>
              <option value="28/35/42 dias">28/35/42 dias</option>
              <option value="28/35/42/49 dias">28/35/42/49 dias</option>
              <option value="28/35/42/56 dias">28/35/42/56 dias</option>
              <option value="28/35/42/56/70 dias">28/35/42/56/70 dias</option>
              <option value="28/35/56 dias">28/35/56 dias</option>
              <option value="28/42 dias">28/42 dias</option>
              <option value="28/42/56 dias">28/42/56 dias</option>
              <option value="28/42/56/70 dias">28/42/56/70 dias</option>
              <option value="28/42/56/70/84 dias">28/42/56/70/84 dias</option>
              <option value="28/56 dias">28/56 dias</option>
              <option value="28/56/72 dias">28/56/72 dias</option>
              <option value="28/56/84 dias">28/56/84 dias</option>
              <option value="28/56/84/112 dias">28/56/84/112 dias</option>
              <option value="28/56/84/112/140 dias">28/56/84/112/140 dias</option>
              <option value="3 Dias">3 Dias</option>
              <option value="3 parcelas">3 parcelas</option>
              <option value="30 dias">30 dias</option>
              <option value="30 Parcelas">30 Parcelas</option>
              <option value="30/42 dias">30/42 dias</option>
              <option value="30/44 dias">30/44 dias</option>
              <option value="30/45 Dias">30/45 Dias</option>
              <option value="30/45/60 dias">30/45/60 dias</option>
              <option value="30/45/60/75 dias">30/45/60/75 dias</option>
              <option value="30/60 dias">30/60 dias</option>
              <option value="30/60/90 dias">30/60/90 dias</option>
              <option value="30/60/90/120 dias">30/60/90/120 dias</option>
              <option value="30/60/90/120/150 dias">30/60/90/120/150 dias</option>
              <option value="30/60/90/120/150/180 dias">30/60/90/120/150/180 dias</option>
              <option value="33 parcelas">33 parcelas</option>
              <option value="35 dias">35 dias</option>
              <option value="35/63 dias">35/63 dias</option>
              <option value="4 parcelas">4 parcelas</option>
              <option value="40 dias">40 dias</option>
              <option value="42 dias">42 dias</option>
              <option value="42/56/70/84 dias">42/56/70/84 dias</option>
              <option value="42/56/70/84/98 dias">42/56/70/84/98 dias</option>
              <option value="45 dias">45 dias</option>
              <option value="5 parcelas">5 parcelas</option>
              <option value="56 dias">56 dias</option>
              <option value="56/84/112 dias">56/84/112 dias</option>
              <option value="56/84/112/140/168 dias">56/84/112/140/168 dias</option>
              <option value="6 parcelas">6 parcelas</option>
              <option value="6 parcelas inicio 14">6 parcelas inicio 14</option>
              <option value="60/90/120 dias">60/90/120 dias</option>
              <option value="60/90/120/150 dias">60/90/120/150 dias</option>
              <option value="60/90/120/150/180/210 dias">60/90/120/150/180/210 dias</option>
              <option value="7 dias">7 dias</option>
              <option value="7 parcelas">7 parcelas</option>
              <option value="7/14 dias">7/14 dias</option>
              <option value="7/14/21 dias">7/14/21 dias</option>
              <option value="7/14/21/28 dias">7/14/21/28 dias</option>
              <option value="7/14/21/28/35 dias">7/14/21/28/35 dias</option>
              <option value="7/14/21/28/35/42/49/56 dias">7/14/21/28/35/42/49/56 dias</option>
              <option value="7/14/21/28/42 dias">7/14/21/28/42 dias</option>
              <option value="7/14/21/28/56/84 dias">7/14/21/28/56/84 dias</option>
              <option value="7/14/21/35/42/56 dias">7/14/21/35/42/56 dias</option>
              <option value="7/14/28 dias">7/14/28 dias</option>
              <option value="7/14/28/35/42 dias">7/14/28/35/42 dias</option>
              <option value="7/14/28/56 dias">7/14/28/56 dias</option>
              <option value="7/14/28/56/84 dias">7/14/28/56/84 dias</option>
              <option value="7/21/28 dias">7/21/28 dias</option>
              <option value="7/21/35 dias">7/21/35 dias</option>
              <option value="7/21/35/49/56 dias">7/21/35/49/56 dias</option>
              <option value="7/28 dias">7/28 dias</option>
              <option value="7/28/35 dias">7/28/35 dias</option>
              <option value="7/28/42 dias">7/28/42 dias</option>
              <option value="7/28/42/56 dias">7/28/42/56 dias</option>
              <option value="7/28/42/56/70/84 dias">7/28/42/56/70/84 dias</option>
              <option value="7/28/56 dias">7/28/56 dias</option>
              <option value="7/28/56/84 dias">7/28/56/84 dias</option>
              <option value="7/28/56/84/112 dias">7/28/56/84/112 dias</option>
              <option value="7/28/56/84/112/140 dias">7/28/56/84/112/140 dias</option>
              <option value="8 parcelas">8 parcelas</option>
              <option value="8 parcelas especial">8 parcelas especial</option>
              <option value="9 parcelas">9 parcelas</option>
              <option value="90 dias">90 dias</option>
              <option value="90/120/150 dias">90/120/150 dias</option>
              <option value="À vista">À vista</option>
              <option value="Antecipado">Antecipado</option>
              <option value="Entrada/28 dias">Entrada/28 dias</option>
              <option value="Entrada/7 dias">Entrada/7 dias</option>
              <option value="Pagamento na retirada">Pagamento na retirada</option>
            </select>
            <input id="obsC_<?= $r['id'] ?>" type="text" class="input-notes" placeholder="Observação (opcional)">
<button  class="btn approve" type="button" onclick="showChk((ck) => aprovarComercial(<?= $r['id'] ?>, ck))">
  Aprovar
</button>
<button class="btn reject" type="button" onclick="rejeitarComercial(<?= $r['id'] ?>)">
  Reijeita
</button>

          
          </div>
        <?php endif; ?>

  <?php if ($role === 'financeiro' || $role === 'admin'): ?>
  <div class="row-actions">
    <select id="acaoF_<?= $r['id'] ?>">
      <option value="">Selecionar ação...</option>
      <option value="Cadastrado">Cadastrado</option>
      <option value="Reprovado_Financeiro">Reprovar</option>
    </select>    
    <?php if ($role ==='admin'): ?>  
      <input id="obsF_<?= $r['id'] ?>" type="text" class="input-motivo" placeholder="Motivo obrigatório se for cancelar">  
      <button type="button" class="btn slv" onclick="corrigirAdmin(<?= $r['id'] ?>)">Salvar</button>
    <?php endif; ?>
    <?php if ($role ==='financeiro'): ?>  
      <input id="obsF_<?= $r['id'] ?>" type="text" class="input-motivo" placeholder="Motivo (obrigatório se reprovar)">  
      <button type="button" class="btn slv" onclick="salvarAcaoFinanceiro(<?= $r['id'] ?>)">Salvar</button>
    <?php endif; ?>
    
    
    </div>
<?php endif; ?>

  <?php if ($role === 'dono'): ?>
    <!-- Dono: apenas ver histórico -->
    <div class="row-actions">
      <button type="button" class="btn ghost" onclick="verHistoricoAjax(<?= (int)$r['id'] ?>)">Ver histórico</button>
      <button type="button" class="btn ghost" onclick="toggleDetalhes(<?= (int)$r['id'] ?>, this)">Ver detalhes</button>
    </div>
  <?php else: ?>
    <!-- Para admin/comercial/financeiro: também exibe o Ver histórico -->
    <div class="row-actions">     
      <button type="button" class="btn ghost" onclick="toggleDetalhes(<?= (int)$r['id'] ?>, this)">Ver detalhes</button>
      <button type="button" class="btn ghost" onclick="verHistoricoAjax(<?= (int)$r['id'] ?>)">Ver histórico</button>
    </div>
  <?php endif; ?>
  </td>
</tr>
<!-- lugar onde o JS vai injetar a linha expandida -->
<tr class="details-row" id="detrow-<?= (int)$r['id'] ?>" style="display:none">
  <td colspan="6" id="detcell-<?= (int)$r['id'] ?>"></td>
</tr>
</td>


    <?php if ((int)($_GET['view'] ?? 0) === (int)$r['id']):
      $st2 = $pdo->prepare("SELECT id, file_name, mime, size 
                            FROM submission_files 
                            WHERE submission_id=? AND (is_active=1 OR is_active IS NULL)
                            ORDER BY uploaded_at DESC, id DESC");
      $st2->execute([$r['id']]);
      $files = $st2->fetchAll(PDO::FETCH_ASSOC);
    ?>
      <tr id="anexos"><td colspan="7">
        <div class="card">
          <h3>Anexos — #<?=$r['id']?></h3>
          <?php if (!$files): ?>
            <p>Sem arquivos vinculados.</p>
          <?php else: ?>
            <ul class="files">
              <?php foreach ($files as $f): ?>
                <li>
                  <span><?=htmlspecialchars($f['file_name'])?></span>
                  <small class="meta"><?=htmlspecialchars($f['mime'] ?? '')?> — <?=number_format(($f['size'] ?? 0)/1024,0,',','.')?> KB</small>
                  <a class="btn" href="javascript:void(0)" onclick="openPreview('/painel/preview.php?id=<?=$r['id']?>&file=<?=$f['id']?>')">Visualizar</a>
                  <a class="btn can-download" href="/painel/download.php?id=<?=$r['id']?>&file=<?=$f['id']?>">Baixar</a>
                  <a class="btn can-upload" href="javascript:void(0)"
   onclick="openReplace(<?= (int)$r['id'] ?>, <?= (int)$f['id'] ?>, '<?= htmlspecialchars($f['file_name'], ENT_QUOTES) ?>')">
  Substituir
</a>
                </li>
              <?php endforeach; ?>
            </ul>
            <?php if ($files):
              $firstUrl = "/painel/preview.php?id={$r['id']}&file={$files[0]['id']}"; ?>
              <a class="btn" href="javascript:void(0)" onclick="openPreview('<?=$firstUrl?>')">Visualizar primeiro</a>
            <?php endif; ?>
          <?php endif; ?>
          <a class="btn ghost download-zip" href="/painel/download_zip.php?id=<?=$r['id']?>">Baixar todos (ZIP)</a>
        </div>
      </td></tr>
    <?php endif; ?>

  <?php endforeach; ?>
  </tbody>
</table>

<?php
$totalPages = max(1, ceil($totalRows / $pageSize));
if ($totalPages > 1): ?>
<nav class="pagination">
  <?php for ($p=1;$p<=$totalPages;$p++):
    $qs = http_build_query(['q'=>$q,'status'=>$status,'page'=>$p]); ?>
    <a class="<?=$p===$page?'active':''?>" href="?<?=$qs?>"><?=$p?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>

<!-- PREVIEW -->
<div id="preview-modal" class="modal" style="display:none">
  <div class="modal-body">
    <button type="button" class="close" onclick="closePreview()">×</button>
    <iframe id="preview-frame" style="width:100%;height:70vh;border:0" title="Pré-visualização"></iframe>
    <!-- <div class="hint">
      <small>Dica: se a imagem/PDF estiver errado, marque <b>Reprovado</b> e descreva o motivo em “Observação”.</small>
    </div> -->
  </div>
</div>

<!-- REPLACE -->
<div id="replace-modal" class="modal" style="display:none">
  <div class="modal-body">
    <button type="button" class="close" onclick="closeReplace()">×</button>
    <h3>Substituir arquivo</h3>
    <form id="replace-form" method="post" action="/painel/upload_replace.php" enctype="multipart/form-data">
      <input type="hidden" name="submission_id" id="rep-submission">
      <input type="hidden" name="old_file_id" id="rep-fileid">
      <label>Arquivo atual: <span id="rep-oldname"></span></label>
      <label>Novo arquivo (PDF/Imagem até 20MB)
        <input type="file" name="newfile" accept=".pdf,image/*" required>
      </label>
      <label>Observação (opcional)
        <input type="text" name="note" placeholder="Ex.: reenviado via WhatsApp">
      </label>
      <button type="submit">Enviar</button>
    </form>
  </div>
</div>

<!-- CHECKLIST (COMERCIAL) -->
<div id="chkModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);align-items:center;justify-content:center;z-index:9999">
  <div style="background:#fff;padding:16px 18px;border-radius:10px;max-width:720px;width:96%;">
    <h3 style="margin:0 0 12px">Checklist de conferência</h3>

    <fieldset style="border:1px solid #ddd;padding:10px 12px;margin:0 0 10px">
      <legend style="padding:0 6px">A) Contrato social</legend>
      <label><input type="checkbox" class="ck-item" data-key="contrato.legivel"> Documento é contrato/alteração social (PDF/IMG legível)</label><br>
    <label><input type="checkbox" class="ck-item" data-key="contrato.razao_social"> Razão social e CNPJ batem com o formulario</label><br>
      <label><input type="checkbox" class="ck-item" data-key="contrato.socios_listados"> Sócios/representantes listados</label><br>
      
    </fieldset>

    <fieldset style="border:1px solid #ddd;padding:10px 12px;margin:0 0 10px">
      <legend style="padding:0 6px">B) Documentos do(s) sócio(s)</legend>
      <label><input type="checkbox" class="ck-item" data-key="socios.doc.ok"> RG/CPF/CNH com foto, nomes conferem</label><br>
      <label><input type="checkbox" class="ck-item" data-key="socios.doc.validade"> Documentos dentro da validade</label><br>
    </fieldset>
    <fieldset style="border:1px solid #ddd;padding:10px 12px;margin:0 0 10px">
      <legend style="padding:0 6px">C) Endereço</legend>
      <label><input type="checkbox" class="ck-item" data-key="endereco.confere"> Endereço do formulário confere com contrato</label><br>
    </fieldset>

    <fieldset style="border:1px solid #ddd;padding:10px 12px;margin:0 0 10px">
      <legend style="padding:0 6px">D) Outros anexos</legend>
      <label><input type="checkbox" class="ck-item" data-key="anexos.loja_interna"> Foto interna da loja</label><br>
      <label><input type="checkbox" class="ck-item" data-key="anexos.fachada"> Foto da fachada</label><br>
      <label><input type="checkbox" class="ck-item" data-key="anexos.comprovante_endereco"> Comprovante de endereço até 90 dias</label><br>
      <label><input type="checkbox" class="ck-item" data-key="anexos.cartao_cnpj"> Cartão/consulta CNPJ legível</label><br>
    </fieldset>

    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
      <button type="button" onclick="hideChk()">Cancelar</button>
      <button type="button" id="chkConfirmBtn">Confirmar</button>
    </div>
  </div>
</div>


<!-- HISTÓRICO -->
<div id="history-modal" class="modal" style="display:none">
  <div class="modal-body" style="max-width:760px;width:92%">
    <button type="button" class="close" onclick="closeHistory()">×</button>
    <h3 id="history-title" style="margin:0 0 12px">Histórico</h3>
    <div id="history-empty" style="display:none;color:#666">Sem histórico.</div>
    <div id="history-table-wrap" style="overflow:auto;max-height:60vh;display:none">
      <table class="table">
        <thead>
          <tr class="data-row"><th>#</th><th>Ação</th><th>Data/Hora</th><th>Por</th><th>Obs.</th></tr>
        </thead>
        <tbody id="hist-body"></tbody>
      </table>
    </div>
  </div>
</div>
<script>
  // passa o BASE do /painel pro JS externo
  window.PAINEL_CONF = {
    BASE: <?= json_encode(rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\')) ?> // ex.: "/painel"
  };
</script>
<script src="/painel/assets/painel.js" defer></script>
</body>
</html>
