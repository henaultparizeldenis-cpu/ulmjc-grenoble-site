<?php /* Panneau d'aperçu en direct ULMJC, commun aux écrans d'administration
   (inclus par admin_footer()). Quand un bénévole édite une actu / activité /
   partenaire, il voit EN DIRECT le rendu de la page publique correspondante avec
   ses modifications NON enregistrées.

   Porté de mohamed-cms/site/admin/_live_preview.php. Adapté à ULMJC :
   - MAPPING par page admin (voir _MAP ci-dessous) : les écrans d'édition postent
     leur formulaire à preview.php?type=… (LIVE) ; les listes chargent simplement
     la page publique correspondante (aperçu non live).
   - L'éditeur de corps ULMJC a l'id « editorArea » (pas « editor ») et le champ
     caché envoyé au serveur est « bodyField ». On synchronise donc editorArea →
     bodyField avant chaque rendu (mêmes chemins « uploads/ » / « images/ » qu'au save).
   - Reskin ULMJC (barre pin, accents terre cuite) : voir les règles .lp-* d'admin.css.
   - Bonus conservé : détachement sur 2ᵉ écran, bascule ordi/mobile, plein écran. */ ?>
<button type="button" class="lp-fab" id="lpFab" aria-label="Afficher l'aperçu du site">👁 Aperçu</button>
<div id="livePreview" data-state="hidden">
  <button type="button" class="lp-tab" id="lpTab" title="Afficher l'aperçu">‹ Aperçu</button>
  <aside class="lp-panel" aria-label="Aperçu du site">
    <div class="lp-bar">
      <span class="lp-title">Aperçu du site</span>
      <div class="lp-tools">
        <button type="button" class="lp-btn lp-device-btn" id="lpDevice" aria-label="Bascule ordinateur / mobile"></button>
        <button type="button" class="lp-btn" id="lpWide" title="Élargir / réduire" aria-label="Élargir / réduire">⤢</button>
        <button type="button" class="lp-btn lp-ico" id="lpFull" title="Plein écran" aria-label="Plein écran"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M21 16v3a2 2 0 0 1-2 2h-3M3 16v3a2 2 0 0 0 2 2h3"/></svg></button>
        <button type="button" class="lp-btn lp-ico" id="lpDetach" title="Détacher (2ᵉ écran)" aria-label="Détacher l'aperçu dans une fenêtre"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6"/><path d="M11 13 20 4"/><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/></svg></button>
        <button type="button" class="lp-btn" id="lpReload" title="Rafraîchir" aria-label="Rafraîchir">↻</button>
        <button type="button" class="lp-btn" id="lpHide" title="Masquer l'aperçu" aria-label="Masquer">✕</button>
      </div>
    </div>
    <div class="lp-body">
      <div class="lp-frame-wrap">
        <iframe id="lpFrame" name="lpFrame" title="Aperçu du site"></iframe>
        <iframe id="lpFrame2" name="lpFrame2" title="Aperçu du site" aria-hidden="true"></iframe>
      </div>
    </div>
  </aside>
  <div class="lp-detached-bar">
    <span class="lp-dbar-label">Aperçu sur l'écran 2</span>
    <button type="button" class="lp-btn lp-device-btn" id="lpDevice2" title="Ordinateur / mobile" aria-label="Bascule ordinateur / mobile"></button>
    <button type="button" class="lp-dbar-reattach" id="lpReattach">↩ Rattacher</button>
  </div>
</div>
<script>
(function () {
  var form = document.querySelector('form.aform'), lp = document.getElementById('livePreview'),
      body = lp.querySelector('.lp-body'), wrap = lp.querySelector('.lp-frame-wrap'),
      panel = lp.querySelector('.lp-panel'), fab = document.getElementById('lpFab'),
      f1 = document.getElementById('lpFrame'), f2 = document.getElementById('lpFrame2');
  if (!lp || !f1) return;
  var LS = 'ulmjc_lp_state', LSD = 'ulmjc_lp_device';
  var POPUP_NAME = 'ulmjcPreviewWin', DETACH = 'ulmjc_lp_detached', win = null;
  function isDetached() { return !!(win && !win.closed); }

  // MAPPING : chaque page admin prévisualise SA page publique.
  //  - « live » = l'aperçu se met à jour depuis le formulaire (rendu via preview.php
  //    qui applique les modifs NON enregistrées) ; on poste alors le formulaire.
  //  - sinon (listes) on charge simplement la page publique correspondante.
  //  - « art » = un éditeur de corps est présent (id editorArea → champ bodyField)
  //    qu'il faut synchroniser avant chaque rendu.
  var _page = (location.pathname.split('/').pop() || 'index.php').toLowerCase();
  var _MAP = {
    // Écrans d'édition (LIVE : on poste le formulaire d'édition à preview.php)
    'edit.php':             { ep: 'preview.php?type=actus',       live: true, art: true, scroll: true },
    'activite-edit.php':    { ep: 'preview.php?type=activites',   live: true, art: true, scroll: true },
    'partenaire-edit.php':  { ep: 'preview.php?type=partenaires', live: true },
    // Listes / gestion (aperçu simple, non live)
    'index.php':            { ep: '../actus.php',        live: false },
    'activites.php':        { ep: '../activites.php',    live: false },
    'partenaires.php':      { ep: '../partenariats.php', live: false },
    'chalet.php':           { ep: '../chalet.php',       live: false }
  };
  // Pages admin non mappées (mot de passe…) : pas de panneau d'aperçu.
  var _cfg = _MAP[_page];
  if (!_cfg) { lp.style.display = 'none'; if (fab) fab.style.display = 'none'; return; }
  var ENDPOINT = _cfg.ep;
  // Éditeur de corps ULMJC : la zone éditable est « editorArea », le champ posté « bodyField ».
  var artEditor = _cfg.art ? document.getElementById('editorArea') : null;
  var artField  = _cfg.art ? document.getElementById('bodyField')  : null;
  var isArticle = !!artEditor;
  var previewable = !!(form && _cfg.live);   // « live » = on poste le formulaire à l'endpoint
  var isMobile = function () { return window.matchMedia('(max-width: 900px)').matches; };
  var cur = f1, pendY = 0;

  try { var s = localStorage.getItem(LS); if (s) lp.setAttribute('data-state', s); } catch (e) {}
  var device = 'laptop'; // toujours « ordinateur » par défaut (le bouton bascule en mobile à la demande)
  if (isMobile() && lp.getAttribute('data-state') !== 'hidden' && !sessionStorage.getItem('ulmjc_lp_m')) lp.setAttribute('data-state', 'hidden');

  function applyLayout() {
    var st = lp.getAttribute('data-state'), det = lp.classList.contains('lp-detached'), open = st !== 'hidden' && !det;
    document.body.classList.toggle('lp-open', open && !isMobile());
    document.body.classList.toggle('lp-wide', !det && st === 'wide' && !isMobile() && device !== 'mobile');
    if (fab) fab.style.display = (isMobile() && !open && !det) ? 'flex' : 'none';
  }
  function setState(st) { lp.setAttribute('data-state', st); try { localStorage.setItem(LS, st); } catch (e) {} applyLayout(); scaleFrame(); }

  function scaleFrame() {
    if (isMobile()) {
      wrap.style.width = '100%'; wrap.style.height = '100%';
      [f1, f2].forEach(function (f) { f.style.width = '100%'; f.style.height = '100%'; f.style.transform = 'none'; });
      return;
    }
    var availW = body.clientWidth, availH = body.clientHeight, DW, sc;
    if (device === 'mobile') {
      DW = Math.min(availW, 480); sc = 1;
    } else {
      DW = 1280; sc = availW / DW; if (sc > 1) sc = 1;
    }
    var DH = Math.max(420, Math.round(availH / sc));
    [f1, f2].forEach(function (f) { f.style.width = DW + 'px'; f.style.height = DH + 'px'; f.style.transformOrigin = 'top left'; f.style.transform = 'scale(' + sc + ')'; });
    wrap.style.width = Math.round(DW * sc) + 'px'; wrap.style.height = availH + 'px';
  }
  window.addEventListener('resize', function () { applyLayout(); scaleFrame(); });
  if (panel) panel.addEventListener('transitionend', function (e) { if (e.propertyName === 'width') scaleFrame(); });

  // Synchronise l'éditeur de corps (editorArea) dans le champ posté (bodyField),
  // en ramenant les chemins d'image « ../uploads/ » / « ../images/ » à « uploads/ »
  // / « images/ » — EXACTEMENT comme le fait le handler de soumission du formulaire.
  function syncEditor() {
    if (isArticle && artField && artEditor) {
      artField.value = artEditor.innerHTML.replace(/src="\.\.\/(uploads|images)\//g, 'src="$1/');
    }
  }

  // Soumet le formulaire (avec les modifs non enregistrées) vers une cible nommée
  // — un iframe du panneau, OU la fenêtre détachée — puis restaure action/target.
  function submitTo(targetName) {
    var oa = form.getAttribute('action'), ot = form.getAttribute('target');
    form.setAttribute('action', ENDPOINT); form.setAttribute('target', targetName);
    try { form.submit(); } catch (e) {}
    if (oa === null) form.removeAttribute('action'); else form.setAttribute('action', oa);
    if (ot === null) form.removeAttribute('target'); else form.setAttribute('target', ot);
  }

  function restorePopupScroll(py) {
    var n = 0;
    var iv = setInterval(function () {
      n++;
      try {
        if (!win || win.closed) { clearInterval(iv); return; }
        if (win.document && win.document.readyState === 'complete') win.scrollTo(0, py);
      } catch (e) {}
      if (n >= 16) clearInterval(iv);
    }, 50);
  }

  function doReload() {
    syncEditor();
    // Fenêtre détachée (2ᵉ écran) : on y pousse l'aperçu, quel que soit l'état du panneau.
    if (isDetached()) {
      if (!previewable) { try { win.location.replace(ENDPOINT + (ENDPOINT.indexOf('?') < 0 ? '?' : '&') + '_=' + (+new Date())); } catch (e) {} return; }
      var py = 0; try { py = win.scrollY || 0; } catch (e) {}
      submitTo(POPUP_NAME);
      if (py) restorePopupScroll(py);
      return;
    }
    if (lp.getAttribute('data-state') === 'hidden') return;
    try { pendY = cur.contentWindow.scrollY || 0; } catch (e) { pendY = 0; }
    var nxt = (cur === f1) ? f2 : f1; nxt.setAttribute('data-loading', '1');
    if (!previewable) { nxt.src = ENDPOINT + (ENDPOINT.indexOf('?') < 0 ? '?' : '&') + '_=' + (+new Date()); return; }
    submitTo(nxt === f1 ? 'lpFrame' : 'lpFrame2');
  }

  // --- Multi-écrans (Chromium / HTTPS) : placer l'aperçu détaché sur l'écran 2. ---
  var screensCache = null;
  function queryWinPerm() {
    if (!navigator.permissions || !navigator.permissions.query) return Promise.reject();
    return navigator.permissions.query({ name: 'window-management' })
      .catch(function () { return navigator.permissions.query({ name: 'window-placement' }); });
  }
  function cacheScreens(sd) {
    screensCache = sd;
    try { sd.addEventListener('screenschange', function () { screensCache = sd; }); } catch (e) {}
  }
  function primeScreens() {
    if (!window.getScreenDetails) return;
    queryWinPerm().then(function (st) {
      if (st && st.state === 'granted') window.getScreenDetails().then(cacheScreens).catch(function () {});
    }).catch(function () {});
  }
  function secondScreen() {
    if (!screensCache) return null;
    return screensCache.screens.filter(function (s) { return !s.isPrimary; })[0] || null;
  }

  function sizePopupForDevice() {
    if (!isDetached()) return;
    var s = secondScreen();
    if (device === 'mobile') {
      var w = 430, h = s ? s.availHeight : 860,
          left = s ? (s.availLeft + Math.round((s.availWidth - w) / 2)) : 80,
          top = s ? s.availTop : 0;
      try { win.resizeTo(w, h); win.moveTo(left, top); } catch (e) {}
    } else if (s) {
      try { win.moveTo(s.availLeft, s.availTop); win.resizeTo(s.availWidth, s.availHeight); } catch (e) {}
    } else {
      try { win.resizeTo(1280, 820); } catch (e) {}
    }
  }

  function detach() {
    var s = secondScreen(), feats;
    if (device === 'mobile') {
      var w = 430, h = s ? s.availHeight : 860,
          left = s ? (s.availLeft + Math.round((s.availWidth - w) / 2)) : 80, top = s ? s.availTop : 0;
      feats = 'left=' + left + ',top=' + top + ',width=' + w + ',height=' + h;
    } else {
      feats = s ? ('left=' + s.availLeft + ',top=' + s.availTop + ',width=' + s.availWidth + ',height=' + s.availHeight) : 'width=1280,height=820';
    }
    var sdPromise = (!s && window.getScreenDetails) ? window.getScreenDetails() : null;
    win = window.open(previewable ? 'about:blank' : (ENDPOINT + (ENDPOINT.indexOf('?') < 0 ? '?' : '&') + '_=' + (+new Date())), POPUP_NAME, feats);
    if (!win) { alert("Autorisez les fenêtres pop-up pour ce site, puis réessayez : l'aperçu s'ouvrira dans une fenêtre déplaçable sur votre 2ᵉ écran."); return; }
    try { sessionStorage.setItem(DETACH, '1'); } catch (e) {}
    win.focus();
    lp.classList.add('lp-detached');
    applyLayout();
    doReload();
    if (sdPromise) sdPromise.then(function (sd) { cacheScreens(sd); sizePopupForDevice(); }).catch(function () {});
  }
  function reattach() {
    try { sessionStorage.removeItem(DETACH); } catch (e) {}
    if (win && !win.closed) { try { win.close(); } catch (e) {} }
    win = null;
    lp.classList.remove('lp-detached');
    applyLayout(); scaleFrame(); doReload();
  }
  var t = null;
  function schedule() { if (lp.getAttribute('data-state') === 'hidden' && !isDetached()) return; if (t) clearTimeout(t); t = setTimeout(doReload, 300); }
  window.ulmjcPreviewRefresh = schedule;

  // Scroll-sync : en défilant l'éditeur (article / activité), l'aperçu suit
  // proportionnellement — la même fraction de défilement.
  if (_cfg.scroll) {
    var _sst;
    window.addEventListener('scroll', function () {
      if (_sst) return;
      _sst = setTimeout(function () {
        _sst = null;
        if (lp.getAttribute('data-state') === 'hidden' && !isDetached()) return;
        var docH = document.documentElement.scrollHeight - window.innerHeight;
        if (docH <= 40) return;
        var frac = Math.min(1, Math.max(0, window.scrollY / docH));
        var w = isDetached() ? win : (cur && cur.contentWindow);
        try {
          var max = w.document.documentElement.scrollHeight - w.innerHeight;
          var y = Math.round(frac * Math.max(0, max));
          w.scrollTo(0, y); pendY = y;
        } catch (e) {}
      }, 60);
    }, { passive: true });
  }

  [f1, f2].forEach(function (f) {
    f.addEventListener('load', function () {
      scaleFrame();
      var y = pendY;
      function put() { try { f.contentWindow.scrollTo(0, y); } catch (e) {} }
      put();
      if (f.getAttribute('data-loading')) {
        f.removeAttribute('data-loading');
        requestAnimationFrame(function () {
          put();
          requestAnimationFrame(function () {
            put();
            f.style.visibility = 'visible';
            if (cur !== f) cur.style.visibility = 'hidden';
            cur = f;
          });
        });
        setTimeout(put, 180);
      }
    });
  });
  f2.style.visibility = 'hidden';

  if (previewable) {
    var scheduleIf = function (e) { if (e && e.target && e.target.closest && e.target.closest('[data-no-preview]')) return; schedule(); };
    form.addEventListener('input', scheduleIf); form.addEventListener('change', scheduleIf);
    // Rattrapage : certains contrôles (choix de média, boutons de mise en forme) posent
    // leur valeur SANS émettre input/change → un clic programme un rafraîchissement.
    // Exclu pour l'éditeur de corps (clics = placement du curseur ; il a keyup ci-dessous).
    if (!isArticle) form.addEventListener('click', scheduleIf);
    // Éditeur de corps : rafraîchit à la frappe (l'éditeur n'émet pas « input » sur le form).
    if (isArticle && artEditor) {
      artEditor.addEventListener('keyup', schedule);
      artEditor.addEventListener('input', schedule);
    }
  }

  function open() { sessionStorage.setItem('ulmjc_lp_m', '1'); setState('open'); doReload(); }
  document.getElementById('lpHide').addEventListener('click', function () {
    if (document.fullscreenElement === panel && document.exitFullscreen) {
      try { document.exitFullscreen().then(function () { setState('hidden'); }, function () { setState('hidden'); }); return; } catch (e) {}
    }
    setState('hidden');
  });
  document.getElementById('lpTab').addEventListener('click', open);
  if (fab) fab.addEventListener('click', open);
  document.getElementById('lpWide').addEventListener('click', function () { setState(lp.getAttribute('data-state') === 'wide' ? 'open' : 'wide'); });
  document.getElementById('lpReload').addEventListener('click', doReload);
  document.getElementById('lpDetach').addEventListener('click', function () { isDetached() ? reattach() : detach(); });
  var reBtn = document.getElementById('lpReattach'); if (reBtn) reBtn.addEventListener('click', reattach);
  // Plein écran (API Fullscreen du navigateur, sur le panneau)
  var fullBtn = document.getElementById('lpFull');
  function isFs() { return document.fullscreenElement === panel; }
  fullBtn.addEventListener('click', function () {
    if (isFs()) { if (document.exitFullscreen) document.exitFullscreen(); }
    else if (panel.requestFullscreen) { try { panel.requestFullscreen().catch(function () {}); } catch (e) {} }
  });
  document.addEventListener('fullscreenchange', function () {
    var fs = isFs(); fullBtn.title = fs ? 'Quitter le plein écran' : 'Plein écran';
    lp.classList.toggle('lp-fs', fs); scaleFrame(); setTimeout(scaleFrame, 120);
  });
  // Bouton appareil : montre l'icône de l'aperçu en cours (ordinateur ↔ téléphone), alterne au clic.
  var LAPTOP_SVG = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="12" rx="1.5"/><path d="M2 20h20"/></svg>';
  var PHONE_SVG = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="3" width="10" height="18" rx="2.5"/><path d="M10.5 18.5h3"/></svg>';
  var devBtn = document.getElementById('lpDevice'), devBtn2 = document.getElementById('lpDevice2');
  function setDeviceIcon() {
    var ico = device === 'mobile' ? PHONE_SVG : LAPTOP_SVG;
    var ttl = device === 'mobile' ? 'Aperçu mobile — cliquer pour ordinateur' : 'Aperçu ordinateur — cliquer pour mobile';
    [devBtn, devBtn2].forEach(function (b) { if (b) { b.innerHTML = ico; b.title = ttl; } });
    lp.classList.toggle('lp-mobileview', device === 'mobile');
  }
  function toggleDevice() {
    device = device === 'laptop' ? 'mobile' : 'laptop';
    setDeviceIcon(); applyLayout(); scaleFrame();
    if (isDetached()) sizePopupForDevice();
  }
  devBtn.addEventListener('click', toggleDevice);
  if (devBtn2) devBtn2.addEventListener('click', toggleDevice);
  setDeviceIcon();

  // Reprise de l'état détaché en changeant de page admin.
  try {
    if (sessionStorage.getItem(DETACH)) {
      var w = window.open('', POPUP_NAME);
      if (w && !w.closed) { win = w; lp.classList.add('lp-detached'); }
      else { try { sessionStorage.removeItem(DETACH); } catch (e) {} }
    }
  } catch (e) {}
  window.addEventListener('focus', function () {
    if (lp.classList.contains('lp-detached') && !isDetached()) {
      win = null; try { sessionStorage.removeItem(DETACH); } catch (e) {}
      lp.classList.remove('lp-detached'); applyLayout(); scaleFrame();
    }
  });

  primeScreens();
  applyLayout(); scaleFrame();
  if (lp.getAttribute('data-state') !== 'hidden' || isDetached()) doReload();
})();
</script>
