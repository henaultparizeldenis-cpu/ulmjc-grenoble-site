<?php
/* Authentification du back-office ULMJC + gabarit d'administration.
   Basé sur mohamed-cms/site/admin/auth.php. Changements :
   - DURCISSEMENT SÉCURITÉ : mots de passe via password_hash()/password_verify()
     (bcrypt) au lieu de sha256 ; AUCUN mot de passe par défaut (voir needs_setup()).
   - Drapeau de session renommé « ulmjc_admin ».
   - Nav admin reskinnée (libellé « Actualités » ; place prévue pour Activités /
     Partenaires / Chalet). Spécifique avocat retiré (demandes, textes, FAQ, légal,
     réglages, aperçu live). Médiathèque conservée (réutilisée par l'éditeur). */

require_once __DIR__ . '/../inc/lib.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Les pages d'administration ne doivent jamais être mises en cache.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/* ---------- Mot de passe (bcrypt, stocké dans admin.json) ---------- */

/* Le mot de passe n'est PAS encore configuré : admin.json absent ou sans empreinte.
   Dans ce cas, la page de login force la création du mot de passe (1re utilisation).
   Il n'existe AUCUN mot de passe par défaut. */
function needs_setup() {
  if (!is_file(ADMIN_FILE)) return true;
  $d = json_decode(file_get_contents(ADMIN_FILE), true);
  return !(is_array($d) && !empty($d['pass']));
}
function admin_pass_hash() {
  if (is_file(ADMIN_FILE)) {
    $d = json_decode(file_get_contents(ADMIN_FILE), true);
    if (is_array($d) && !empty($d['pass'])) return $d['pass'];
  }
  return '';
}
function set_admin_pass($plain) {
  if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0775, true);
  $hash = password_hash((string)$plain, PASSWORD_DEFAULT); // bcrypt
  if ($hash === false || $hash === null) return false;
  return file_put_contents(ADMIN_FILE, json_encode(array('pass' => $hash)), LOCK_EX) !== false;
}
function check_admin_pass($plain) {
  $hash = admin_pass_hash();
  return $hash !== '' && password_verify((string)$plain, $hash);
}

function is_logged_in() { return !empty($_SESSION['ulmjc_admin']); }
function require_login() { if (!is_logged_in()) { header('Location: index.php'); exit; } }

/* ---------- CSRF ---------- */
function csrf_token() {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_ok() { return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf']); }

/* ---------- Gabarit ---------- */
function admin_header($title) {
  $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
  echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
     . '<meta name="viewport" content="width=device-width,initial-scale=1">'
     . '<title>' . $t . ' — Administration ULMJC</title>'
     . '<link rel="preconnect" href="https://fonts.googleapis.com">'
     . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
     . '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet">'
     . '<link rel="stylesheet" href="admin.css?v=' . (@filemtime(__DIR__ . '/admin.css') ?: '1') . '">'
     . '<script>try{window.name="ulmjc_admin";}catch(e){}</script>'
     . '</head><body>';
  if (is_logged_in()) {
    $cur = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $navlink = function ($href, $label) use ($cur) {
      $act = (strtok($href, '#') === $cur) ? ' class="anav-active" aria-current="page"' : '';
      return '<a href="' . $href . '"' . $act . '>' . $label . '</a>';
    };
    echo '<header class="abar"><div class="abar-inner">'
       . '<a class="abrand" href="../index.html" target="ulmjc_site">← Retour au site</a>'
       . '<button type="button" class="anav-toggle" aria-label="Menu" aria-expanded="false" aria-controls="anav"><span></span><span></span><span></span></button>'
       . '<nav class="anav" id="anav">'
       . $navlink('index.php', 'Actualités')
       // Place prévue pour les prochains types de contenu :
       // . $navlink('activites.php', 'Activités')
       // . $navlink('partenaires.php', 'Partenaires')
       // . $navlink('chalet.php', 'Chalet')
       . '<a href="#" onclick="if(window.openMediaPicker){openMediaPicker();}return false;">Médiathèque</a>'
       . $navlink('password.php', 'Mot de passe')
       . '<a href="logout.php" class="alogout" aria-label="Déconnexion">Déconnexion</a>'
       . '</nav></div></header>'
       . '<script>(function(){var b=document.querySelector(".anav-toggle"),n=document.getElementById("anav");if(b&&n)b.addEventListener("click",function(){var o=n.classList.toggle("open");b.classList.toggle("open",o);b.setAttribute("aria-expanded",o?"true":"false");});})();</script>';
  }
  echo '<main class="awrap">';
}
function admin_footer() {
  echo '</main>';
  // Une seule case « Voir le mot de passe » qui révèle tous les champs de la page.
  echo '<script>(function(){'
     . 'var pws=document.querySelectorAll("input[type=password]"); if(!pws.length)return;'
     . 'var lab=document.createElement("label"); lab.className="pw-show";'
     . 'var cb=document.createElement("input"); cb.type="checkbox";'
     . 'cb.addEventListener("change",function(){for(var i=0;i<pws.length;i++)pws[i].type=cb.checked?"text":"password";});'
     . 'lab.appendChild(cb); lab.appendChild(document.createTextNode(pws.length>1?" Voir les mots de passe":" Voir le mot de passe"));'
     . 'pws[pws.length-1].insertAdjacentElement("afterend",lab);'
     . '})();</script>';
  // Sélecteur de médiathèque réutilisable (openMediaPicker(callback[, {multiple:true}])).
  if (is_logged_in()) {
    echo '<script>window.__CSRF=' . json_encode(csrf_token()) . ';</script>';
    echo <<<'HTML'
<div class="mp-modal" id="mediaPickerModal" hidden>
  <div class="mp-backdrop" data-mp-close></div>
  <div class="mp-panel">
    <div class="mp-head">
      <span class="mp-title">Médiathèque</span>
      <button type="button" class="abtn mp-validate" id="mpValidate" hidden>Valider la sélection (0)</button>
      <label class="abtn abtn-ghost mp-upbtn">Importer une image<input type="file" id="mpUpload" accept="image/jpeg,image/png,image/webp"></label>
      <button type="button" class="abtn abtn-ghost" data-mp-close>Fermer</button>
    </div>
    <div class="mp-grid" id="mpGrid"><p class="mp-empty">Chargement…</p></div>
  </div>
</div>
<script>
(function(){
  var modal=document.getElementById('mediaPickerModal'); if(!modal)return;
  var grid=document.getElementById('mpGrid'), up=document.getElementById('mpUpload'),
      validate=document.getElementById('mpValidate'), titleEl=modal.querySelector('.mp-title');
  var cb=null, multi=false, sel=[];
  function rel(s){ return '../'+s; }
  function closeMp(){ modal.hidden=true; cb=null; multi=false; sel=[]; }
  window.openMediaPicker=function(onPick,opts){
    cb=onPick||null; multi=!!(opts&&opts.multiple); sel=[];
    if(validate) validate.hidden=!multi;
    if(titleEl) titleEl.textContent=multi?'Médiathèque — choisissez plusieurs images':'Médiathèque';
    syncValidate(); modal.hidden=false; load();
  };
  function syncValidate(){ if(validate){ validate.textContent='Valider la sélection ('+sel.length+')'; validate.disabled=sel.length===0; } }
  function relabel(){
    Array.prototype.forEach.call(grid.querySelectorAll('.mp-cell'),function(c){
      var n=c.querySelector('.mp-num'); if(!n)return;
      var i=sel.indexOf(c.getAttribute('data-src'));
      if(i>=0){ n.textContent=(i+1); n.hidden=false; c.classList.add('mp-cell--sel'); }
      else { n.hidden=true; c.classList.remove('mp-cell--sel'); }
    });
  }
  function toggle(src){ var i=sel.indexOf(src); if(i>=0) sel.splice(i,1); else sel.push(src); relabel(); syncValidate(); }
  function load(){
    grid.innerHTML='<p class="mp-empty">Chargement…</p>';
    fetch('media-list.php').then(function(r){return r.json();}).then(function(j){
      if(!j.items||!j.items.length){ grid.innerHTML='<p class="mp-empty">Aucune image pour le moment. Importez-en une.</p>'; return; }
      grid.innerHTML='';
      j.items.forEach(function(it){
        var cell=document.createElement('div'); cell.className='mp-cell'; cell.setAttribute('data-src',it.src);
        var b=document.createElement('button'); b.type='button'; b.className='mp-pick';
        b.style.backgroundImage="url('"+rel(it.src)+"')"; b.title=it.name;
        b.addEventListener('click',function(){ if(!cb)return; if(multi){ toggle(it.src); } else { cb(it.src); closeMp(); } });
        if(!cb) b.style.cursor='default';
        cell.appendChild(b);
        if(multi){ var n=document.createElement('span'); n.className='mp-num'; n.hidden=true; cell.appendChild(n); }
        if(it.del){
          var d=document.createElement('button'); d.type='button'; d.className='mp-del'; d.textContent='×';
          d.addEventListener('click',function(e){ e.stopPropagation();
            if(!confirm("Supprimer cette image ? Si elle est utilisée quelque part, l'emplacement deviendra vide.")) return;
            var fd=new FormData(); fd.append('csrf',window.__CSRF); fd.append('src',it.src);
            fetch('media-delete.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){ if(res.error){alert(res.error);return;} var k=sel.indexOf(it.src); if(k>=0) sel.splice(k,1); syncValidate(); load(); });
          });
          cell.appendChild(d);
        }
        grid.appendChild(cell);
      });
      relabel();
    }).catch(function(){ grid.innerHTML='<p class="mp-empty">Erreur de chargement.</p>'; });
  }
  if(validate) validate.addEventListener('click',function(){ if(!multi||!cb||!sel.length)return; var picked=sel.slice(), fn=cb; closeMp(); fn(picked); });
  if(up) up.addEventListener('change',function(){
    var f=up.files&&up.files[0]; if(!f)return;
    grid.innerHTML='<p class="mp-empty">Envoi…</p>';
    var fd=new FormData(); fd.append('csrf',window.__CSRF); fd.append('file',f);
    fetch('upload.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j){ up.value=''; if(j.error)alert('Import : '+j.error); load(); }).catch(function(){ alert('Import impossible.'); load(); });
  });
  Array.prototype.forEach.call(modal.querySelectorAll('[data-mp-close]'),function(el){ el.addEventListener('click',closeMp); });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape'&&!modal.hidden)closeMp(); });
})();
</script>
HTML;
  }
  echo '</body></html>';
}
