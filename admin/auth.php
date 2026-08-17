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
  /* On réécrit le fichier avec la SEULE empreinte : cela efface volontairement
     les jetons « se souvenir de moi » et le jeton de réinitialisation. Changer
     de mot de passe déconnecte donc TOUS les appareils mémorisés, c'est le
     comportement attendu quand on soupçonne que le mot de passe a fuité. */
  return file_put_contents(ADMIN_FILE, json_encode(array('pass' => $hash)), LOCK_EX) !== false;
}
function check_admin_pass($plain) {
  $hash = admin_pass_hash();
  return $hash !== '' && password_verify((string)$plain, $hash);
}

/* ---------- Mot de passe oublié (réinitialisation par email) ----------
   Adresse de destination FIGÉE (jamais saisie par l'utilisateur) → personne ne
   peut détourner la réinitialisation vers sa propre boîte. Un jeton à usage unique
   (haché en base, expire en 1 h) est envoyé par email ; le lien permet de choisir
   un nouveau mot de passe. Secours d'urgence : supprimer admin.json (ulmjc-data). */
define('RESET_EMAIL', 'ulmjc.gre@free.fr');
define('RESET_FROM',  'no-reply@ulmjcgrenoble.org');
define('RESET_ADMIN_URL', 'https://site.ulmjcgrenoble.org/admin/'); // figé (anti host-header)
define('RESET_TTL', 3600); // 1 heure

function _admin_data() {
  $d = is_file(ADMIN_FILE) ? json_decode(file_get_contents(ADMIN_FILE), true) : array();
  return is_array($d) ? $d : array();
}
function _admin_data_save($d) {
  if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0775, true);
  $j = json_encode($d);
  if ($j === false) return false;
  return file_put_contents(ADMIN_FILE, $j, LOCK_EX) !== false;
}

/* Génère un jeton, stocke son empreinte + expiration, renvoie le jeton en clair.
   Renvoie '' s'il n'y a pas de compte à réinitialiser. */
function create_reset_token() {
  $d = _admin_data();
  if (empty($d['pass'])) return '';
  $token = bin2hex(random_bytes(32));
  $d['reset_hash']    = password_hash($token, PASSWORD_DEFAULT);
  $d['reset_expires'] = time() + RESET_TTL;
  if (!_admin_data_save($d)) return '';
  return $token;
}
function check_reset_token($token) {
  $token = (string)$token;
  if ($token === '') return false;
  $d = _admin_data();
  if (empty($d['reset_hash']) || empty($d['reset_expires'])) return false;
  if (time() > (int)$d['reset_expires']) return false;
  return password_verify($token, $d['reset_hash']);
}
/* Valide le jeton et fixe le nouveau mot de passe (usage unique). */
function consume_reset_and_set_pass($token, $newplain) {
  if (!check_reset_token($token)) return false;
  $d = _admin_data();
  $hash = password_hash((string)$newplain, PASSWORD_DEFAULT);
  if ($hash === false || $hash === null) return false;
  $d['pass'] = $hash;
  unset($d['reset_hash'], $d['reset_expires']);
  return _admin_data_save($d);
}
function send_reset_email($token) {
  $link = RESET_ADMIN_URL . '?reset=' . urlencode($token);
  $subject = 'Réinitialisation du mot de passe (Espace de publication ULMJC)';
  $body =
      "Bonjour,\r\n\r\n"
    . "Une réinitialisation du mot de passe de l'espace de publication du site ULMJC a été demandée.\r\n\r\n"
    . "Pour choisir un nouveau mot de passe, ouvrez ce lien (valable 1 heure) :\r\n"
    . $link . "\r\n\r\n"
    . "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email : le mot de passe actuel reste inchangé.\r\n\r\n"
    . "Site de l'Union Locale des MJC de Grenoble";
  $headers = "From: ULMJC Grenoble <" . RESET_FROM . ">\r\n"
           . "Reply-To: " . RESET_EMAIL . "\r\n"
           . "Content-Type: text/plain; charset=UTF-8\r\n"
           . "MIME-Version: 1.0\r\n";
  $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
  return @mail(RESET_EMAIL, $encSubject, $body, $headers);
}

/* ---------- « Se souvenir de moi » (connexion persistante) ----------
   Un jeton aléatoire est déposé dans un cookie longue durée ; côté serveur on
   ne conserve que son EMPREINTE (SHA-256), jamais le jeton lui-même : quelqu'un
   qui lirait admin.json ne pourrait pas s'en servir pour se connecter.

   Le jeton est RENOUVELÉ à chaque reconnexion automatique (rotation) : un jeton
   volé cesse de fonctionner dès que la personne légitime revient sur le site.
   Chaque appareil a le sien, et « Déconnexion » ne révoque que celui-là. */

define('REMEMBER_COOKIE', 'ulmjc_rm');
define('REMEMBER_DAYS', 30);

function remember_tokens() {
  $d = is_file(ADMIN_FILE) ? json_decode(file_get_contents(ADMIN_FILE), true) : array();
  return (is_array($d) && !empty($d['remember']) && is_array($d['remember'])) ? $d['remember'] : array();
}

function remember_save_tokens($list) {
  $d = is_file(ADMIN_FILE) ? json_decode(file_get_contents(ADMIN_FILE), true) : array();
  if (!is_array($d)) $d = array();
  // Purge des jetons expirés à chaque écriture : le fichier ne gonfle pas.
  $now = time();
  $d['remember'] = array_values(array_filter($list, function ($t) use ($now) {
    return !empty($t['exp']) && $t['exp'] > $now;
  }));
  return file_put_contents(ADMIN_FILE, json_encode($d), LOCK_EX) !== false;
}

function remember_cookie_params($maxAge) {
  return array(
    'expires'  => $maxAge > 0 ? time() + $maxAge : 1,
    'path'     => '/admin/',                       // jamais envoyé au site public
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,                            // inaccessible au JavaScript
    'samesite' => 'Lax',
  );
}

/* Dépose un nouveau jeton pour CET appareil. */
function remember_issue() {
  $tok = bin2hex(random_bytes(32));
  $list = remember_tokens();
  $list[] = array('h' => hash('sha256', $tok), 'exp' => time() + REMEMBER_DAYS * 86400);
  remember_save_tokens($list);
  setcookie(REMEMBER_COOKIE, $tok, remember_cookie_params(REMEMBER_DAYS * 86400));
}

/* Oublie le jeton de cet appareil (les autres restent valides). */
function remember_forget() {
  $tok = $_COOKIE[REMEMBER_COOKIE] ?? '';
  if ($tok !== '') {
    $h = hash('sha256', $tok);
    $list = array_filter(remember_tokens(), function ($t) use ($h) {
      return !hash_equals((string)($t['h'] ?? ''), $h);
    });
    remember_save_tokens($list);
  }
  setcookie(REMEMBER_COOKIE, '', remember_cookie_params(0));
  unset($_COOKIE[REMEMBER_COOKIE]);
}

/* Tente une reconnexion automatique depuis le cookie. */
function remember_try_login() {
  if (!empty($_SESSION['ulmjc_admin'])) return true;
  $tok = $_COOKIE[REMEMBER_COOKIE] ?? '';
  if ($tok === '' || !preg_match('/^[a-f0-9]{64}$/', $tok)) return false;
  if (needs_setup()) return false;              // aucun mot de passe défini : rien à restaurer

  $h = hash('sha256', $tok);
  $now = time();
  foreach (remember_tokens() as $t) {
    if (!empty($t['h']) && !empty($t['exp']) && $t['exp'] > $now && hash_equals((string)$t['h'], $h)) {
      session_regenerate_id(true);
      $_SESSION['ulmjc_admin'] = true;
      remember_forget();   // rotation : l'ancien jeton est révoqué…
      remember_issue();    // …et remplacé par un neuf
      return true;
    }
  }
  // Cookie présent mais inconnu (expiré ou révoqué) : on le nettoie.
  remember_forget();
  return false;
}

function is_logged_in() {
  if (!empty($_SESSION['ulmjc_admin'])) return true;
  return remember_try_login();
}
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
     . '<title>' . $t . ' | Administration ULMJC</title>'
     . '<link rel="preconnect" href="https://fonts.googleapis.com">'
     . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
     . '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet">'
     . '<link rel="stylesheet" href="admin.css?v=' . (@filemtime(__DIR__ . '/admin.css') ?: '1') . '">'
     /* Application installable (PWA) : le back-office peut être ajouté à l'écran
        d'accueil d'un téléphone et s'ouvre alors en plein écran, sans navigateur.
        Portée limitée à /admin/, le site public n'est pas concerné. */
     . '<link rel="manifest" href="manifest.webmanifest">'
     . '<meta name="theme-color" content="#16302A">'
     . '<meta name="mobile-web-app-capable" content="yes">'
     . '<meta name="apple-mobile-web-app-capable" content="yes">'
     . '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">'
     . '<meta name="apple-mobile-web-app-title" content="ULMJC Publication">'
     . '<link rel="apple-touch-icon" href="icons/apple-touch-icon.png">'
     . '<link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">'
     . '<script>try{window.name="ulmjc_admin";}catch(e){}</script>'
     . '</head><body>';
  if (is_logged_in()) {
    $cur = basename($_SERVER['SCRIPT_NAME'] ?? '');
    /* Chaque section regroupe sa page de liste ET ses écrans d'édition, pour que
       l'onglet reste surligné pendant la création/modification d'un élément. */
    $sections = array(
      'index.php'      => array('index.php', 'edit.php', 'save.php', 'delete.php'),
      'blog.php'       => array('blog.php', 'billet-edit.php', 'billet-save.php', 'billet-delete.php'),
      'activites.php'  => array('activites.php', 'activite-edit.php', 'activite-save.php', 'activite-delete.php'),
      'partenaires.php'=> array('partenaires.php', 'partenaire-edit.php', 'partenaire-save.php', 'partenaire-delete.php'),
      'emplois.php'    => array('emplois.php', 'offre-edit.php', 'offre-save.php', 'offre-delete.php'),
      'chalet.php'     => array('chalet.php', 'chalet-save.php'),
      'corbeille.php'  => array('corbeille.php', 'corbeille-action.php'),
    );
    $navlink = function ($href, $label) use ($cur, $sections) {
      $base = strtok($href, '#');
      $group = isset($sections[$base]) ? $sections[$base] : array($base);
      $act = in_array($cur, $group, true) ? ' class="anav-active" aria-current="page"' : '';
      return '<a href="' . $href . '"' . $act . '>' . $label . '</a>';
    };
    /* Lien « Corbeille » avec badge du nombre d'éléments à la corbeille (tous types). */
    $trashN   = trashed_count();
    $trashAct = in_array($cur, $sections['corbeille.php'], true) ? ' class="anav-active" aria-current="page"' : '';
    $trashBadge = $trashN > 0 ? ' <span class="anav-badge">' . $trashN . '</span>' : '';
    $corbeilleLink = '<a href="corbeille.php"' . $trashAct . '>Corbeille' . $trashBadge . '</a>';
    echo '<header class="abar"><div class="abar-inner">'
       . '<a class="abrand" href="../index.php" target="ulmjc_site">← Retour au site</a>'
       . '<button type="button" class="anav-toggle" aria-label="Menu" aria-expanded="false" aria-controls="anav"><span></span><span></span><span></span></button>'
       . '<nav class="anav" id="anav">'
       . $navlink('index.php', 'Actualités')
       . $navlink('blog.php', 'Blog')
       . $navlink('activites.php', 'Activités')
       . $navlink('partenaires.php', 'Partenaires')
       . $navlink('emplois.php', 'Offres d\'emploi')
       . $navlink('chalet.php', 'Photos chalet')
       /* Application de GESTION du chalet (réservations, devis, factures) : elle
          vit sur l'autre domaine et possède ses propres comptes individuels. On
          ne fait que pointer vers elle, elle refuse d'ailleurs, à raison, d'être
          affichée dans un cadre (X-Frame-Options: DENY). Ouverture dans un nouvel
          onglet pour ne pas perdre le travail en cours côté site. */
       . '<a class="anav-ext" href="https://ulmjcgrenoble.org/" target="ulmjc_gestion" rel="noopener"'
       . ' title="Réservations, devis, factures (application séparée, connexion propre)">'
       . 'Gestion du chalet <span aria-hidden="true">↗</span></a>'
       . '<a href="#" onclick="if(window.openMediaPicker){openMediaPicker();}return false;">Médiathèque</a>'
       . $corbeilleLink
       . $navlink('password.php', 'Mot de passe')
       . '<a href="logout.php" class="alogout" aria-label="Déconnexion">Déconnexion</a>'
       . '</nav></div></header>'
       . '<script>(function(){var b=document.querySelector(".anav-toggle"),n=document.getElementById("anav");if(b&&n)b.addEventListener("click",function(){var o=n.classList.toggle("open");b.classList.toggle("open",o);b.setAttribute("aria-expanded",o?"true":"false");});})();</script>';
  }
  echo '<main class="awrap">';
}
function admin_footer() {
  echo '</main>';
  /* Enregistrement du service worker : condition de l'installation sur mobile.
     Il ne met en cache QUE le statique (voir sw.js), jamais les pages, qui
     dépendent de la session et changeraient sous les pieds du rédacteur. */
  echo '<script>if("serviceWorker" in navigator){window.addEventListener("load",function(){'
     . 'navigator.serviceWorker.register("sw.js",{scope:"./"}).catch(function(){});});}</script>';
  /* Éditeur d'image (rogner / redimensionner), utilisé depuis la médiathèque. */
  if (is_logged_in()) {
    echo '<script src="media-editor.js?v=' . (@filemtime(__DIR__ . '/media-editor.js') ?: '1') . '" defer></script>';
  }
  /* Panneau d'aperçu du site en direct (commun aux écrans d'administration connectés).
     _live_preview.php choisit lui-même s'il s'affiche selon la page (mapping interne :
     écrans d'édition = aperçu LIVE via preview.php ; listes = aperçu simple ; autres =
     masqué). Ajouté ici comme dans mohamed-cms/site/admin/auth.php. Placé AVANT le reste
     pour que le sélecteur de médiathèque (plus bas) reste inchangé. */
  if (is_logged_in()) include __DIR__ . '/_live_preview.php';
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
    /* Le pare-feu de l'hébergeur rejette (403) tout envoi dont le NOM de fichier
       contient une apostrophe ou des caractères exotiques, cas typique des
       « Capture d'écran ….png ». Le serveur renomme de toute façon chaque image
       (img-timestamp-alea.jpg), donc le nom d'origine ne sert à rien : on le
       neutralise avant l'envoi, pour les imports AJAX comme pour les formulaires. */
    echo <<<'HTML'
<script>
window.__safeUploadName = function (name) {
  var ext = (String(name).match(/\.([a-zA-Z0-9]{1,5})$/) || [,'jpg'])[1].toLowerCase();
  return 'photo-' + Date.now() + '-' + Math.floor(Math.random() * 9000 + 1000) + '.' + ext;
};
/* Formulaires classiques : on remplace le fichier par une copie au nom neutre. */
document.addEventListener('submit', function (e) {
  var form = e.target;
  if (!form || !form.querySelectorAll) return;
  if (typeof DataTransfer === 'undefined' || typeof File === 'undefined') return;
  Array.prototype.forEach.call(form.querySelectorAll('input[type=file]'), function (inp) {
    if (!inp.files || !inp.files.length) return;
    if (!/['"\\]/.test(Array.prototype.map.call(inp.files, function (f) { return f.name; }).join(''))) return;
    try {
      var dt = new DataTransfer();
      Array.prototype.forEach.call(inp.files, function (f) {
        dt.items.add(new File([f], window.__safeUploadName(f.name), { type: f.type }));
      });
      inp.files = dt.files;
    } catch (err) { /* navigateur trop ancien : on laisse passer tel quel */ }
  });
}, true);
</script>
HTML;
    echo <<<'HTML'
<div class="mp-modal" id="mediaPickerModal" hidden>
  <div class="mp-backdrop" data-mp-close></div>
  <div class="mp-panel">
    <div class="mp-head">
      <span class="mp-title">Médiathèque</span>
      <button type="button" class="abtn mp-validate" id="mpValidate" hidden>Valider la sélection (0)</button>
      <label class="abtn abtn-ghost mp-upbtn">Importer des images<input type="file" id="mpUpload" accept="image/jpeg,image/png,image/webp" multiple></label>
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
    if(titleEl) titleEl.textContent=multi?'Médiathèque : choisissez plusieurs images':'Médiathèque';
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
        /* Rotation : uniquement pour les photos importées (it.del = fichier de
           uploads/). Une photo de images/ est versionnée et serait de toute
           façon écrasée au prochain déploiement. */
        if(it.del){
          var rots=document.createElement('div'); rots.className='mp-rot';
          [['gauche','↺','Pivoter à gauche'],['droite','↻','Pivoter à droite']].forEach(function(r){
            var rb=document.createElement('button'); rb.type='button'; rb.className='mp-rot-btn';
            rb.textContent=r[1]; rb.title=r[2];
            rb.addEventListener('click',function(e){ e.stopPropagation();
              rb.disabled=true;
              var fd=new FormData(); fd.append('csrf',window.__CSRF);
              fd.append('src',it.src); fd.append('sens',r[0]);
              fetch('media-rotate.php',{method:'POST',body:fd})
                .then(function(x){return x.json();})
                .then(function(res){
                  rb.disabled=false;
                  if(res.error){ alert(res.error); return; }
                  /* Le navigateur a l'ancienne version en cache : on force le
                     rechargement de la vignette avec un paramètre changeant. */
                  b.style.backgroundImage="url('"+rel(it.src)+"?v="+res.v+"')";
                })
                .catch(function(){ rb.disabled=false; alert("La rotation n'a pas abouti."); });
            });
            rots.appendChild(rb);
          });
          cell.appendChild(rots);

          /* Rogner / redimensionner : ouvre l'éditeur. Au retour, on recharge la
             vignette avec un paramètre changeant, sinon le navigateur réafficherait
             l'ancienne version depuis son cache. */
          var cr=document.createElement('button'); cr.type='button'; cr.className='mp-crop';
          cr.textContent='✂'; cr.title='Rogner ou redimensionner';
          cr.addEventListener('click',function(e){ e.stopPropagation();
            if(!window.openImageEditor){ alert("L'éditeur d'image n'est pas disponible."); return; }
            window.openImageEditor(it.src, rel(it.src), function(v){
              b.style.backgroundImage="url('"+rel(it.src)+"?v="+v+"')";
            });
          });
          cell.appendChild(cr);
        }
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
  // Import multiple : on envoie les fichiers un par un (chacun passe par
  // optimize_image côté serveur), avec une progression « n/total ».
  if(up) up.addEventListener('change',function(){
    var files=up.files?Array.prototype.slice.call(up.files):[]; if(!files.length)return;
    var total=files.length, errors=0, motifs=[];
    (function step(i){
      if(i>=total){
        up.value='';
        if(errors){
          var uniq=motifs.filter(function(m,k){return motifs.indexOf(m)===k;});
          alert(errors+' image(s) sur '+total+' n\'ont pas pu être importées.\n\nMotif : '+uniq.join('\n'));
        }
        load(); return;
      }
      grid.innerHTML='<p class="mp-empty">Import en cours… '+(i+1)+'/'+total+'</p>';
      var fd=new FormData(); fd.append('csrf',window.__CSRF);
      /* 3e argument = nom transmis : neutralisé (voir __safeUploadName). */
      fd.append('file',files[i],window.__safeUploadName?window.__safeUploadName(files[i].name):'photo.jpg');
      /* On lit la réponse en TEXTE puis on tente le JSON : si le serveur (ou un
         pare-feu) renvoie une page HTML d'erreur, r.json() échouerait et on
         perdrait la cause réelle. Ici on la remonte à l'utilisateur. */
      fetch('upload.php',{method:'POST',body:fd})
        .then(function(r){ return r.text().then(function(t){ return {status:r.status, text:t}; }); })
        .then(function(res){
          var j=null; try{ j=JSON.parse(res.text); }catch(e){}
          if(j && j.url){ /* import réussi */ }
          else if(j && j.error){ errors++; motifs.push(j.error+' (HTTP '+res.status+')'); }
          else {
            var brut=(res.text||'').replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim().slice(0,140);
            errors++; motifs.push('Réponse inattendue du serveur, HTTP '+res.status+(brut?' : '+brut:''));
          }
          step(i+1);
        })
        .catch(function(){ errors++; motifs.push('Requête bloquée avant d\'atteindre le serveur (réseau, extension ou pare-feu).'); step(i+1); });
    })(0);
  });
  Array.prototype.forEach.call(modal.querySelectorAll('[data-mp-close]'),function(el){ el.addEventListener('click',closeMp); });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape'&&!modal.hidden)closeMp(); });
})();
</script>
HTML;
  }
  echo '</body></html>';
}
