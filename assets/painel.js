// ====== Config base ======
const BASE = (window.PAINEL_CONF && window.PAINEL_CONF.BASE) || '/painel';
const API_UPD      = `${BASE}/api/submissions/update.php`;
const API_HISTORY  = `${BASE}/api/submissions/history.php`;
const API_DETAILS  = `${BASE}/api/submissions/details.php`;
console.log('[painel.js] BASE=', BASE);
const API_EMAIL    = `${BASE}/api/submissions/enviaEmail.php`;

// ====== Preview / Replace ======
window.openPreview = function(url){
  const f = document.getElementById('preview-frame');
  f.src = url;
  document.getElementById('preview-modal').style.display = 'flex';
};
window.closePreview = function(){
  const f = document.getElementById('preview-frame');
  f.src = 'about:blank';
  document.getElementById('preview-modal').style.display = 'none';
};

window.openReplace = function(subId, fileId, oldName){
  console.log('[openReplace] subId=', subId, 'fileId=', fileId, 'oldName=', oldName);
  document.getElementById('rep-submission').value = subId;
  document.getElementById('rep-fileid').value     = fileId;
  document.getElementById('rep-oldname').textContent = oldName;
  document.getElementById('replace-modal').style.display = 'flex';
};

// Validação no submit, pegando os campos do PRÓPRIO form enviado
document.addEventListener('DOMContentLoaded', () => {
  const frm = document.getElementById('replace-form');
  if (!frm) return;

  frm.addEventListener('submit', (e) => {
    const form = e.currentTarget;

    const subIdEl  = form.querySelector('#rep-submission');
    const fileIdEl = form.querySelector('#rep-fileid');
    const fileInp  = form.querySelector('input[type="file"]');

    const subId  = +(subIdEl?.value || 0);
    const fileId = +(fileIdEl?.value || 0);
    const hasFile = !!(fileInp?.files?.length);

    if (!subId || !fileId) {
      e.preventDefault();
      alert('IDs não preenchidos (submission_id / old_file_id). Abra o modal via "Substituir".');
      return;
    }
    if (!hasFile) {
      e.preventDefault();
      alert('Selecione um arquivo.');
      return;
    }

    // log de conferência (opcional)
    console.log('[replace-form] POST =>', { subId, fileId, file: fileInp.files[0]?.name });
  });
});

window.closeReplace = function(){
  document.getElementById('replace-modal').style.display = 'none';
};
// ===== Checklist: inicialização única =====
if (!window.__chkInit) {
  window.__chkInit = true;

  // guardamos a função que será chamada ao confirmar
  window.__approveAction = null;

  window.showChk = function (fn) {
    window.__approveAction = (typeof fn === 'function') ? fn : null;
    const m = document.getElementById('chkModal');
    if (m) m.style.display = 'flex';
  };

  window.hideChk = function () {
    const m = document.getElementById('chkModal');
    if (m) m.style.display = 'none';
    // NÃO limpe __approveAction aqui; ela será usada logo após fechar o modal
  };

  document.addEventListener('DOMContentLoaded', () => {
    const btnOk = document.getElementById('chkConfirmBtn');
    if (!btnOk) return;

    btnOk.addEventListener('click', async () => {
      const ck = readChecklistFromModal(); // sua função que monta o JSON
      const allOk = ['contrato','socios','endereco','anexos'].every(s => ck[s]?.ok === true);
      if (!allOk) { alert('Marque todos os itens do checklist.'); return; }

      if (typeof window.__approveAction !== 'function') {
        alert('Ação de aprovação não registrada.');
        return;
      }

      hideChk();
      await window.__approveAction(ck); // chama aprovarComercial(id, ck)
      // opcional: limpar depois de usar
      window.__approveAction = null;
    });
  });
}


// === Lê o checklist do modal e retorna o JSON que o backend espera ===
// Lê o checklist do modal e retorna no formato que o backend espera
function readChecklistFromModal() {
  const items = Array.from(document.querySelectorAll('.ck-item, .chkitem'));
  const obsInputs = Array.from(document.querySelectorAll('[data-sec]'));

  const ck = {
    contrato: { ok: false, obs: '' },
    socios:   { ok: false, obs: '' },
    endereco: { ok: false, obs: '' },
    anexos: {
      ok: false, obs: '',
      loja_interna: false,
      fachada: false,
      comprovante_endereco: false,
      cartao_cnpj: false
    }
  };

  for (const el of items) {
    const key = (el.dataset.key || '').trim();
    if (!key) continue;
    const [sec, sub] = key.split('.');
    if (!['contrato','socios','endereco','anexos'].includes(sec)) continue;
    if (sec === 'anexos' && sub) ck.anexos[sub] = el.checked;
    else if (ck[sec]) ck[sec].ok = (ck[sec].ok || el.checked);
  }

  // Se todos os anexos obrigatórios estiverem true, seta ok = true
  ck.anexos.ok =
    ck.anexos.loja_interna &&
    ck.anexos.fachada &&
    ck.anexos.comprovante_endereco &&
    ck.anexos.cartao_cnpj;

  for (const o of obsInputs) {
    const sec = (o.dataset.sec || '').trim();
    if (!sec) continue;
    if (sec === 'anexos') ck.anexos.obs = (o.value || '').trim();
    else if (['contrato','socios','endereco'].includes(sec))
      ck[sec].obs = (o.value || '').trim();
  }

  return ck;
}







// ====== Helpers ======
async function postJSON(url, data){
  const res = await fetch(url, {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(data)
  });
  const text = await res.text();
  try { return JSON.parse(text); }
  catch {
    console.error('Resposta não-JSON do endpoint:', text);
    alert('Falha na chamada: a resposta não é JSON. Verifique o caminho: '+url);
    return { ok:false };
  }
}
function val(id){ const el = document.getElementById(id); return el ? el.value.trim() : ''; }
function checked(id){ const el = document.getElementById(id); return el ? !!el.checked : false; }
function escapeHTML(str){
  return String(str ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'",'&#039;');
}
// Abre o checklist SOMENTE se consultor e condição estiverem preenchidos
window.abrirChecklistAprovacao = function(id){
  const consultor = val('consultor_'+id);
  const cond      = val('cond_'+id);
  if(!consultor){ alert('Selecione o consultor.'); return; }
  if(!cond){ alert('Selecione a condição de vendas.'); return; }
  // registra a ação a executar ao confirmar
  showChk((ck)=>aprovarComercial(id, ck));
};



// Função para avisar o financeiro via e-mail

// function avisarFinanceiro() {
//   fetch(API_EMAIL, { method: 'POST' })
//     .then(r => r.json())
//     .then(data => {
//       if (data.ok) {
//         console.log('Financeiro avisado');
//       } else {
//         console.log('Erro ao enviar e-mail:', data.erro);
//       }
//     })
//     .catch(err => {
//       console.error('Falha na chamada:', err);
//     });
// }

// function avisarFinanceiro() {
//   fetch(API_EMAIL, { method: 'POST' })
//     .then(async (r) => {
//       const text = await r.text(); // pega resposta crua como texto
//       console.log('RESPOSTA BRUTA DO PHP >>>', text);

//       // tenta fazer parse manual depois de logar
//       try {
//         const data = JSON.parse(text);

//         if (data.ok) {
//           console.log('Financeiro avisado');
//         } else {
//           console.log('Erro ao enviar e-mail:', data.erro);
//         }
//       } catch (e) {
//         console.error('Nao consegui converter pra JSON. Resposta veio assim ↑');
//       }
//     })
//     .catch(err => {
//       console.error('Falha na chamada fetch:', err);
//     });
// }



// ====== Ações principais (expostas no window) ======
// recebe o ck vindo do modal (obrigatório)
window.aprovarComercial = async function(id, ck){
  const consultor = val('consultor_'+id);
  const cond      = val('cond_'+id);
  const obs       = val('obsC_'+id);

  if(!consultor){ alert('Selecione o consultor.'); return; }
  if(!cond){ alert('Selecione a condição de vendas.'); return; }
  if(!ck || typeof ck !== 'object'){ 
    alert('Checklist obrigatório para aprovar.'); 
    return; 
  }

  // NÃO stringify: o backend decodifica php://input para array
  const j = await postJSON(API_UPD, {
    id,
    status: 'Analise_Financeiro',
    consultor,
    condicao_vendas: cond,
    obs,
    checklist: ck
  });
  
  const f = await postJSON(API_EMAIL, { status: 'cadastrado' });
 // dispara email assíncrono (não espera resposta)

  if(!j.ok){ alert(j.msg || 'Falha ao aprovar'); return; }
  location.reload();
};

window.rejeitarComercial = async function(id){
  const obs = val('obsC_'+id);

  if(!obs){
    alert('Informe o motivo da rejeicao comercial.');
    return;
  }

  const j = await postJSON(API_UPD, {
    id,
    status: 'Rejeitado_Comercial',
    obs
  });

  if(!j.ok){ alert(j.msg || 'Falha ao rejeitar no comercial'); return; }
  location.reload();
};


window.corrigirAdmin = async function(id){
  const acao = val('acaoF_'+id);
  const obs  = val('obsF_'+id);

  if(!acao){ alert('Selecione a acao do financeiro.'); return; }
  if(acao === 'Reprovado_Financeiro' && !obs){
    alert('Motivo e obrigatorio para reprovar.');
    return;
  }

  const j = await postJSON(API_UPD, { id, status: acao, obs });
  if(!j.ok){ alert(j.msg || 'Falha ao salvar'); return; }
  location.reload();
};




window.salvarAcaoFinanceiro = async function(id){
  const acao = val('acaoF_'+id);  // 'cadastrado' | 'reprovado_financeiro'
  const obs  = val('obsF_'+id);
  if(!acao){ alert('Selecione a ação do financeiro.'); return; }
  if(acao === 'reprovado_financeiro' && !obs){
    alert('Motivo é obrigatório para reprovar.');
    return;
  }
  const j = await postJSON(API_UPD, { id, status: acao, obs });
  const c = await postJSON(API_EMAIL, { status: acao, obs });
  
  console.log('Resposta do email:', c);
  if(!j.ok){ alert(j.msg || 'Falha ao salvar'); return; }  
  location.reload();
};

// ====== Histórico (AJAX) ======
window.verHistoricoAjax = async function(id){
  try{
    const url = `${API_HISTORY}?id=${encodeURIComponent(id)}`;
    const res = await fetch(url);
    const j = await res.json();
    if(!j.ok){ alert(j.msg || 'Falha ao carregar histórico'); return; }
    renderHistorico(id, j.data || []);
  }catch(e){
    console.error(e);
    alert('Erro ao carregar histórico');
  }
};
window.closeHistory = function(){
  document.getElementById('history-modal').style.display = 'none';
};
function renderHistorico(id, rows){
  const modal = document.getElementById('history-modal');
  const title = document.getElementById('history-title');
  const tbody = document.getElementById('hist-body');
  const empty = document.getElementById('history-empty');
  const wrap  = document.getElementById('history-table-wrap');

  title.textContent = 'Histórico — #' + id;
  tbody.innerHTML = '';

  if (!rows.length){
    empty.style.display = 'block';
    wrap.style.display  = 'none';
  } else {
    empty.style.display = 'none';
    wrap.style.display  = 'block';
    rows.forEach((r, i) => {
      const tr = document.createElement('tr');
      tr.innerHTML =
        `<td>${i+1}</td>`+
        `<td>${acaoBadge(r.acao)}</td>`+
        `<td>${fmtDateTime(r.changed_at)}</td>`+
        `<td>${escapeHTML(r.user_name || ('#'+(r.by_user || '')))}</td>`+
        `<td>${escapeHTML(r.obs || '')}</td>`;
      tbody.appendChild(tr);
    });
  }
  modal.style.display = 'flex';
}
function fmtDateTime(s){
  if(!s) return '';
  const [date, time] = String(s).split(' ');
  if(!date) return s;
  const [Y,m,d] = date.split('-');
  return `${d}/${m}/${Y} ${time||''}`;
}
function acaoBadge(acao){
  const map = {
    comercial_aprovou:   ['Aprovado (Comercial)','green'],
    comercial_rejeitou:  ['Rejeitado (Comercial)','red'],
    financeiro_reprovou: ['Reprovado (Financeiro)','red'],
    financeiro_cadastrou:['Cadastrado (Financeiro)','gray']
  };
  const [label, color] = map[acao] || [acao, 'gray'];
  return `<span class="pill ${color}">${escapeHTML(label)}</span>`;
}

// ====== Detalhes on-demand ======
let __openDetailsId = null;

window.toggleDetalhes = async function(id, btn){
  // se já está aberto, fecha
  if (__openDetailsId === id){
    hideDetalhes(id);
    return;
  }
  // fecha anterior, se houver
  if (__openDetailsId) hideDetalhes(__openDetailsId);

  const row  = document.getElementById(`detrow-${id}`);
  const cell = document.getElementById(`detcell-${id}`);
  if (!row || !cell) return;

  cell.innerHTML = `<div style="padding:12px;color:#666">Carregando detalhes…</div>`;
  row.style.display = '';

  try{
    const res = await fetch(`${API_DETAILS}?id=${encodeURIComponent(id)}`);
    const j = await res.json();
    if(!j.ok){ cell.innerHTML = `<div style="padding:12px;color:#a00">Falha: ${escapeHTML(j.msg||'erro ao carregar')}</div>`; return; }

    cell.innerHTML = renderDetalhesHTML(j.data || {});
    __openDetailsId = id;
    if (btn) btn.textContent = 'Ocultar detalhes';
  }catch(e){
    console.error(e);
    cell.innerHTML = `<div style="padding:12px;color:#a00">Erro ao carregar detalhes.</div>`;
  }
};
function hideDetalhes(id){
  const row = document.getElementById(`detrow-${id}`);
  if (row) row.style.display = 'none';
  const btn = document.querySelector(`button[onclick="toggleDetalhes(${id}, this)"]`);
  if (btn) btn.textContent = 'Ver detalhes';
  __openDetailsId = null;
}

// ====== Util de sim/não + linhas condicionais ======
function ynNorm(v){
  const s = String(v ?? '').trim().toLowerCase();
  if (['1','sim','s','yes','y','true','proprio','próprio'].includes(s)) return 'sim';
  if (['0','nao','não','n','no','false'].includes(s)) return 'nao';
  return ''; // desconhecido
}
function renderYNDetail(label, flagVal, detailText, detailWhen = 'sim'){
  const f = ynNorm(flagVal); // 'sim' | 'nao' | ''
  const showDetail = (detailWhen === 'nao') ? (f === 'nao') : (f === 'sim');
  const base = (f === 'sim') ? 'Sim' : (f === 'nao') ? 'Não' : '—';
  const extra = (showDetail && detailText) ? ` — ${escapeHTML(detailText)}` : '';
  return `<div><b>${escapeHTML(label)}:</b> ${base}${extra}</div>`;
}

// ====== Checklist summary (em "Ver detalhes") ======
function renderChecklistSummary(ck, when){
  if (!ck) return '';
  const row = (sec, label) => {
    const ok  = ck[sec]?.ok === true ? '✅' : '❌';
    const obs = ck[sec]?.obs ? ` — <i>${escapeHTML(ck[sec].obs)}</i>` : '';
    return `<div>${ok} <b>${escapeHTML(label)}</b>${obs}</div>`;
  };
  const meta = when ? `<small>Conferido em ${escapeHTML(when)}.</small>` : '';
  return `
    <div class="card">
      <h3>Checklist (Comercial)</h3>
      <div class="det-grid">
        ${row('contrato','Contrato social')}
        ${row('socios','Documentos do(s) sócio(s)')}
        ${row('endereco','Endereço')}
        ${row('anexos','Outros anexos')}
      </div>
      ${meta}
    </div>
  `;
}

// ====== Render dos detalhes ======
function renderDetalhesHTML(d){
  const esc = (s)=> String(s ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;');

  // Condicional "Como conheceu / Por que parou"
  const tipo = String(d.tipocadastro || d.tipo_cadastro || '').trim().toLowerCase();
  const resp = String(d.respostatipocadastro || d.resposta_tipo_cadastro || '').trim();
  const linhaComoConheceuOuParou = resp
    ? (tipo === 'novo'
        ? `<div class="full"><b>Como conheceu a Kazza:</b> ${esc(resp)}</div>`
        : `<div class="full"><b>Por que parou de comprar com a Kazza:</b> ${esc(resp)}</div>`
      )
    : '';

  // Condicionais Sim/Não
  const equipeHtml   = renderYNDetail('Equipe de vendas', d.equipe_flag, d.equipe_qtd ? `${d.equipe_qtd} pessoa(s)` : '');
  const ramoHtml     = renderYNDetail('Ramo de decorações', d.ramo_flag, d.ramo_det, 'nao');
  const toldoHtml    = renderYNDetail('Comercializa toldo', d.toldo_flag, d.toldo_qtd);
  const showroomHtml = renderYNDetail('Possui showroom', d.showroom_flag, d.showroom_det);

  // Resumo
  const resumo = `
    <div class="det-grid">
      <div><b>Tipo:</b> ${esc(d.tipo_cadastro || '—')}</div>
      ${linhaComoConheceuOuParou}
      <div><b>Razão Social:</b> ${esc(d.razao_social || '—')}</div>
      <div><b>Telefone:</b> ${esc(d.telefone || '—')}</div>
      <div><b>CNPJ:</b> ${esc(d.cnpj || '—')}</div>
      <div><b>E-mail:</b> ${esc(d.email || '—')}</div>
      <div><b>Nome Fantasia:</b> ${esc(d.nome_fantasia || '—')}</div>
      <div class="full"><b>Endereço:</b> ${esc(d.endereco_full || '—')}</div>
      <div><b>Inscrição Estadual:</b> ${esc(d.inscricao_estadual || '—')}</div>
      <div><b>Constituição:</b> ${esc(d.data_constituicao_br || '—')}</div>
      <div><b>Transportadora:</b> ${esc(d.transportadora || '—')}</div>
      ${ramoHtml}
      ${equipeHtml}
      ${showroomHtml}
      ${toldoHtml}
      <div><b>Loja Física:</b> ${esc(d.loja_fisica || '—')}</div>
      <div><b>Instalador:</b> ${esc(d.instalador || '—')}</div>
      <div><b>Faturamento:</b> ${esc(d.faturamento_mensal || '—')}</div>
      <div><b>Site/Instagram:</b> ${esc(d.site_instagram || '—')}</div>
      <div><b>Quantidade de sócios:</b> ${esc(d.quantidade_socios || '—')}</div>

      <div class="col2"><b>Consultor:</b> ${esc(d.consultor || '—')}</div>
      <div class="col2"><b>Condição de vendas:</b> ${esc(d.condicao_vendas || '—')}</div>
      <div class="col2"><b>Principal produto:</b> ${esc(d.principal_produto || '—')}</div>
      <div class="col2"><b>Principal fornecedor:</b> ${esc(d.principal_fornecedor || '—')}</div>
      <div class="col2"><b>Porque buscou a Kazza:</b> ${esc(d.motivodaparceria || d.como_conheceu || '—')}</div>
      <div class="full"><b>Proprietário:</b> ${esc(d.proprietario_nome || '—')} — CPF: ${esc(d.proprietario_cpf || '—')} — Nasc.: ${esc(d.proprietario_nasc || '—')}</div>
    </div>
  `;

  // Anexos
function fmtKB(n){ return n ? `${Math.round(n/1024)} KB` : ''; }

const anexosList = (d.anexos || []).map(a => {
  const meta = `${esc(a.nome || '')} — ${fmtKB(a.size)}`;

  const btnView = a.url_view
    ? `<a class="btn" href="javascript:openPreview('${a.url_view}')">Visualizar</a>`
    : '<span class="cell--empty"></span>';

  const btnDownload = a.url_download
    ? `<a class="btn" href="${a.url_download}">Baixar</a>`
    : '<span class="cell--empty"></span>';

  const btnReplace = a.url_replace
    ? `<a class="btn" href="javascript:void(0)" onclick="openReplace(${d.id}, ${a.id}, '${esc(a.nome)}')">Substituir</a>`
    : '<span class="cell--empty"></span>';

  // 4 colunas: meta | visualizar | baixar | substituir
  return `<li>
    <span class="meta">${meta}</span>
    ${btnView}
    ${btnDownload}
    ${btnReplace}
  </li>`;
}).join('');


  const anexos = `
    <div class="card">
      <h3>Anexos</h3>
      ${anexosList ? `<ul class="files">${anexosList}</ul>` : `<div style="color:#666">Sem arquivos.</div>`}
      ${d.zip_download ? `<a class="btn ghost" href="${d.zip_download}">Baixar todos (ZIP)</a>` : ''}
    </div>`;

  return `
    <div class="card">
      <h3>Detalhes — ${esc(d.razao_social || '')}</h3>
      ${resumo}
    </div>

    ${renderChecklistSummary(d.checklist, d.checklist_at)}

    ${anexos}
  `;
}



