/* Éditeur d'image sommaire du back-office ULMJC : rogner et redimensionner.

   Ouvert depuis la médiathèque (bouton ✂ sur une photo importée).
   Le cadre de sélection se déplace et se redimensionne à la souris ou au doigt.
   Les formats proposés correspondent à ceux réellement utilisés par le site —
   3:2 pour les bandeaux d'article, 16:10 pour les vignettes de cartes — afin
   qu'on voie exactement ce qui restera visible.

   Rien n'est envoyé tant qu'on n'a pas cliqué sur « Appliquer ». */
(function () {
  'use strict';

  var ov, img, box, srcPath, natW, natH, ratio = null, onDone = null;

  function el(tag, cls, txt) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (txt != null) e.textContent = txt;
    return e;
  }

  function build() {
    ov = el('div', 'ie-ov');
    ov.hidden = true;

    var bar = el('div', 'ie-bar');
    bar.appendChild(el('span', 'ie-title', "Rogner l'image"));

    var fmts = el('div', 'ie-fmts');
    [['Libre', null], ['3:2 (bandeau)', 3 / 2], ['16:10 (vignette)', 16 / 10], ['Carré', 1]]
      .forEach(function (f, i) {
        var b = el('button', 'ie-fmt' + (i === 0 ? ' on' : ''), f[0]);
        b.type = 'button';
        b.addEventListener('click', function () {
          ov.querySelectorAll('.ie-fmt').forEach(function (x) { x.classList.remove('on'); });
          b.classList.add('on');
          ratio = f[1];
          resetBox();
        });
        fmts.appendChild(b);
      });
    bar.appendChild(fmts);

    var close = el('button', 'ie-close', '✕');
    close.type = 'button'; close.title = 'Fermer';
    close.addEventListener('click', hide);
    bar.appendChild(close);
    ov.appendChild(bar);

    var stage = el('div', 'ie-stage');
    img = el('img', 'ie-img');
    box = el('div', 'ie-box');
    ['nw', 'ne', 'sw', 'se'].forEach(function (p) {
      var h = el('span', 'ie-h ie-h-' + p);
      h.setAttribute('data-h', p);
      box.appendChild(h);
    });
    stage.appendChild(img);
    stage.appendChild(box);
    ov.appendChild(stage);

    var foot = el('div', 'ie-foot');
    var info = el('span', 'ie-info', '');
    var reduce = el('label', 'ie-reduce');
    var chk = el('input'); chk.type = 'checkbox'; chk.id = 'ieReduce';
    reduce.appendChild(chk);
    reduce.appendChild(document.createTextNode(' Réduire à 1200 px de large'));
    var apply = el('button', 'abtn', 'Appliquer');
    apply.type = 'button';
    var cancel = el('button', 'ie-cancel', 'Annuler');
    cancel.type = 'button';
    cancel.addEventListener('click', hide);
    foot.appendChild(info);
    foot.appendChild(reduce);
    foot.appendChild(cancel);
    foot.appendChild(apply);
    ov.appendChild(foot);

    apply.addEventListener('click', function () { send(apply, info, chk.checked); });
    ov.addEventListener('click', function (e) { if (e.target === ov) hide(); });
    document.addEventListener('keydown', function (e) { if (!ov.hidden && e.key === 'Escape') hide(); });

    document.body.appendChild(ov);
    drag();
    return ov;
  }

  /* Sélection initiale : toute l'image, ou le plus grand rectangle au format
     demandé, centré. */
  function resetBox() {
    var r = img.getBoundingClientRect(), s = ov.querySelector('.ie-stage').getBoundingClientRect();
    var w = r.width, h = r.height;
    if (ratio) {
      if (w / h > ratio) w = h * ratio; else h = w / ratio;
    }
    place((r.left - s.left) + (r.width - w) / 2, (r.top - s.top) + (r.height - h) / 2, w, h);
  }

  function place(l, t, w, h) {
    var r = img.getBoundingClientRect(), s = ov.querySelector('.ie-stage').getBoundingClientRect();
    var minL = r.left - s.left, minT = r.top - s.top;
    w = Math.max(24, Math.min(w, r.width));
    h = Math.max(24, Math.min(h, r.height));
    l = Math.max(minL, Math.min(l, minL + r.width - w));
    t = Math.max(minT, Math.min(t, minT + r.height - h));
    box.style.left = l + 'px'; box.style.top = t + 'px';
    box.style.width = w + 'px'; box.style.height = h + 'px';
    var f = natW / r.width;
    ov.querySelector('.ie-info').textContent =
      Math.round(w * f) + ' × ' + Math.round(h * f) + ' px  (original ' + natW + ' × ' + natH + ')';
  }

  /* Déplacement du cadre et de ses poignées, souris et tactile. */
  function drag() {
    var mode = null, sx = 0, sy = 0, st = null;
    function pt(e) { var t = e.touches ? e.touches[0] : e; return { x: t.clientX, y: t.clientY }; }

    box.addEventListener('mousedown', start);
    box.addEventListener('touchstart', start, { passive: false });

    function start(e) {
      var h = e.target.getAttribute && e.target.getAttribute('data-h');
      mode = h || 'move';
      var p = pt(e); sx = p.x; sy = p.y;
      st = { l: parseFloat(box.style.left), t: parseFloat(box.style.top), w: box.offsetWidth, h: box.offsetHeight };
      e.preventDefault();
      document.addEventListener('mousemove', move);
      document.addEventListener('touchmove', move, { passive: false });
      document.addEventListener('mouseup', stop);
      document.addEventListener('touchend', stop);
    }
    function move(e) {
      if (!mode) return;
      var p = pt(e), dx = p.x - sx, dy = p.y - sy;
      e.preventDefault();
      if (mode === 'move') { place(st.l + dx, st.t + dy, st.w, st.h); return; }
      var l = st.l, t = st.t, w = st.w, h = st.h;
      if (mode.indexOf('e') > -1) w = st.w + dx;
      if (mode.indexOf('w') > -1) { w = st.w - dx; l = st.l + dx; }
      if (mode.indexOf('s') > -1) h = st.h + dy;
      if (mode.indexOf('n') > -1) { h = st.h - dy; t = st.t + dy; }
      if (ratio) {
        h = w / ratio;
        if (mode.indexOf('n') > -1) t = st.t + (st.h - h);
      }
      place(l, t, w, h);
    }
    function stop() {
      mode = null;
      document.removeEventListener('mousemove', move);
      document.removeEventListener('touchmove', move);
      document.removeEventListener('mouseup', stop);
      document.removeEventListener('touchend', stop);
    }
  }

  function send(btn, info, reduce) {
    var r = img.getBoundingClientRect(), s = ov.querySelector('.ie-stage').getBoundingClientRect();
    var f = natW / r.width;                      // écran -> pixels réels
    var x = Math.round((parseFloat(box.style.left) - (r.left - s.left)) * f);
    var y = Math.round((parseFloat(box.style.top) - (r.top - s.top)) * f);
    var w = Math.round(box.offsetWidth * f);
    var h = Math.round(box.offsetHeight * f);

    var fd = new FormData();
    fd.append('csrf', window.__CSRF);
    fd.append('src', srcPath);
    fd.append('x', x); fd.append('y', y); fd.append('w', w); fd.append('h', h);
    if (reduce) fd.append('maxw', '1200');

    btn.disabled = true; info.textContent = 'Enregistrement…';
    fetch('media-crop.php', { method: 'POST', body: fd })
      .then(function (res) { return res.json(); })
      .then(function (j) {
        btn.disabled = false;
        if (j.error) { info.textContent = 'Échec : ' + j.error; return; }
        if (onDone) onDone(j.v);
        hide();
      })
      .catch(function () { btn.disabled = false; info.textContent = 'Échec : serveur injoignable.'; });
  }

  function hide() { if (ov) ov.hidden = true; }

  /* API publique : openImageEditor(chemin, url, callbackAprèsEnregistrement) */
  window.openImageEditor = function (src, url, done) {
    if (!ov) build();
    srcPath = src; onDone = done; ratio = null;
    ov.querySelectorAll('.ie-fmt').forEach(function (x, i) { x.classList.toggle('on', i === 0); });
    ov.hidden = false;
    img.onload = function () { natW = img.naturalWidth; natH = img.naturalHeight; resetBox(); };
    img.src = url + (url.indexOf('?') > -1 ? '&' : '?') + 'v=' + Date.now();
  };
})();
