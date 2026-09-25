/* Carte en relief des maisons de l'union, page les-mjc.php.

   TROIS CHOSES A SAVOIR AVANT DE TOUCHER A CE FICHIER.

   1. Ce n'est pas de la 3D WebGL. La scene est un empilement de plaques, une
      par courbe de niveau, a son altitude. Vue de dessus, les dessiner de la
      plus basse a la plus haute donne l'occultation juste, sans tampon de
      profondeur. La projection axonometrique etant affine, changer d'angle ne
      coute que deux multiplications par point : environ 3 ms par image pour
      treize mille points, contre 16,7 ms de budget a 60 images par seconde.
      MapLibre a ete essaye avec tuiles vectorielles, photo aerienne et
      terrain, et les trois ramaient. Ne pas y revenir.

   2. L'occultation repose sur un REMPLISSAGE OPAQUE de la couleur du fond.
      Toute transparence sur les plaques laisse reapparaitre les courbes
      qu'elles sont censees masquer, et le relief redevient plat. C'est aussi
      pourquoi le fond du panneau reste une couleur PLATE : la vignette est
      peinte par-dessus, a la fin, comme le hero du site pose la sienne.

   3. Les maisons ne sont PAS dans geo/carte.json : elles sont lues dans la
      page, sur les attributs data-points des fiches, que le back-office
      alimente. Ajouter une maison dans l'outil la fait donc apparaitre ici
      sans retoucher les donnees. Seuls le relief, les rues, l'eau et le bati
      sont precalcules.

   Sources : relief Terrain Tiles (AWS Open Data), rues, rivieres et batiments
   de la BD TOPO de l'IGN, adresses geocodees sur la Base Adresse Nationale.

   ATTENTION : geo/carte.json et geo/bati.json sont fabriques par des scripts
   Python qui ne sont PAS dans le depot pour l'instant. Cette geometrie est
   figee et n'a pas vocation a bouger, mais tant que la chaine de fabrication
   n'est pas versionnee, ces deux fichiers ne sont pas regenerables ici. */
(function () {
  'use strict';

  var SC = document.getElementById('mjc-carte');
  /* Le conteneur est un div : c'est le canevas qu'on cree qui doit savoir
     dessiner. Tester getContext sur le div faisait sortir tout de suite. */
  if (!SC || !document.createElement('canvas').getContext) return;
  if (!window.fetch || !window.requestAnimationFrame) return;

  /* ------------------------------------------------------- reglages ---
     Figes : le prototype avait des curseurs, ils ont servi a choisir. */
  var PENTE = .5;          // inclinaison de la vue
  var RELIEF = 1;          // echelle verticale : 1 = terrain a sa vraie hauteur
  var TOUR = .5;           // degres par seconde
  var CADENCE = 3;         // vitesse du souffle des courbes et du courant
  var RUES = 4;            // densite du reseau de rues
  var ZOOM = 11, ZOOM_FORT = 26, RETRAIT = .3;
  var PERIODE = 1.5;       // battement des jalons

  var CARTE = 'geo/carte.json', BATI_URL = 'geo/bati.json';

  /* ------------------------------------------------------- palette ---
     Reprise de css/style.css : pin, creme, terre cuite. */
  var SOL = [26, 51, 40], ENCRE = [251, 247, 241];
  var EAU = [18, 56, 76], EAU_TRAIT = [86, 142, 172], COURANT = [176, 219, 238];
  var TERRA = [196, 98, 58];
  var VIGNETTE = 'rgba(8,18,13,.45)';
  var TON_FINE = .40, TON_FORTE = .70;
  var TON_RUE = [0, .92, .74, .54, .36], LARG_RUE = [0, 1.5, .9, .5, .35];
  var TON_MUR = [.08, .15, .25], TON_TOIT = 0, TON_ARETE = .46;

  function rgb(c, a) {
    return a === undefined ? 'rgb(' + c.join(',') + ')'
                           : 'rgba(' + c.join(',') + ',' + a + ')';
  }
  /* Un ton entre 0 (couleur du sol) et 1 (couleur de l'encre). On passe par le
     sol plutot que par la transparence : sinon les plaques laissent voir ce
     qu'elles viennent de masquer. */
  function gris(t, a) {
    t = Math.max(0, Math.min(1, t));
    var c = [0, 0, 0], k;
    for (k = 0; k < 3; k++) c[k] = Math.round(SOL[k] + (ENCRE[k] - SOL[k]) * t);
    return rgb(c, a);
  }

  /* ----------------------------------------------------------- etat ---- */
  var CR, XR, CN, XN, PLAN = null, BATI = null, BATI_EN_COURS = false;
  var MAISONS = [], ECRAN = [];
  var VUE = { az: 0, s: 1, dx: 0, dy: 0, ca: 1, sa: 0, zm: 1, panx: 0, pany: 0, zoomEff: 1 };
  var VISEE = { i: null, de: null, q: 1, duree: 2, p: 0, u: 0, but: 0, survol: null,
                zc: ZOOM, zbut: ZOOM };
  var ECH = null, FICHE = null, MACHINE = null;
  var HORLOGE = 0, MS0 = 0, PREC = 0, VISIBLE = true;
  var SOBRE = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ----------------------------------------------------- projection ---- */
  function angles() {
    var a = VUE.az * Math.PI / 180;
    VUE.ca = Math.cos(a); VUE.sa = Math.sin(a);
  }
  function px(e, n) { return (e * VUE.ca + n * VUE.sa) * VUE.s + VUE.dx; }
  function py(e, n, z) {
    return (-(-e * VUE.sa + n * VUE.ca) * PENTE - (z - PLAN.z0) * RELIEF) * VUE.s + VUE.dy;
  }

  /* Le cadrage s'exprime en echelle et en centre : c'est ce qui permet
     d'interpoler proprement de la vue d'ensemble au gros plan. Interpoler
     separement l'echelle et le decalage ferait deriver le point vise. */
  function cadre() {
    var M = 46, k, x, y;
    VUE.s = 1; VUE.dx = VUE.dy = 0;
    var x0 = 1e9, x1 = -1e9, y0 = 1e9, y1 = -1e9;
    for (k = 0; k < ECH.length; k += 3) {
      x = px(ECH[k], ECH[k + 1]); y = py(ECH[k], ECH[k + 1], ECH[k + 2]);
      if (x < x0) x0 = x; if (x > x1) x1 = x;
      if (y < y0) y0 = y; if (y > y1) y1 = y;
    }
    var s0 = Math.min((CR.w - 2 * M) / Math.max(1, x1 - x0),
                      (CR.h - 2 * M) / Math.max(1, y1 - y0));
    var cx = (x0 + x1) / 2, cy = (y0 + y1) / 2;

    VUE.zoomEff = 1;
    if (VISEE.i !== null && VISEE.u > 0 && MAISONS[VISEE.i]) {
      var b = MAISONS[VISEE.i], e = b.e, nn = b.n, z = b.z, zoom = VISEE.zc;
      if (VISEE.de !== null && VISEE.q < 1 && MAISONS[VISEE.de]) {
        /* Le vol d'une maison a l'autre. On interpole la position AU SOL :
           la vue tourne pendant le trajet, un glissement a l'ecran passerait
           a cote du terrain. Et le drone prend de la hauteur a mi-parcours,
           ce qui evite aussi de survoler les zones sans bati. */
        var a = MAISONS[VISEE.de], q = douceur(VISEE.q);
        e = a.e + (b.e - a.e) * q;
        nn = a.n + (b.n - a.n) * q;
        z = a.z + (b.z - a.z) * q;
        zoom = VISEE.zc * Math.pow(RETRAIT, Math.sin(Math.PI * VISEE.q));
      }
      cx += (px(e, nn) - cx) * VISEE.u;
      cy += (py(e, nn, z) - cy) * VISEE.u;
      VUE.zoomEff = Math.pow(zoom, VISEE.u);
      s0 *= VUE.zoomEff;
    }
    VUE.zoomEff *= VUE.zm;
    VUE.s = s0 * VUE.zm;
    VUE.dx = CR.w / 2 - cx * VUE.s + VUE.panx;
    VUE.dy = CR.h / 2 - cy * VUE.s + VUE.pany;
  }
  function douceur(p) { return p < .5 ? 4 * p * p * p : 1 - Math.pow(2 - 2 * p, 3) / 2; }

  /* ------------------------------------------------------- etiquettes ---
     Un seul mecanisme : la liste des boites deja posees, et chaque nom se
     decale verticalement jusqu'a trouver sa place. Sans cela, chaque angle
     de rotation amene sa collision. */
  var BOITES = [];
  function police(t, g, esp, serif) {
    XR.font = g + ' ' + t + 'px ' + (serif ? "'Lora',Georgia,serif" : "'Inter',system-ui,sans-serif");
    if ('letterSpacing' in XR) XR.letterSpacing = (esp || 0) + 'px';
  }
  function largeur(s, t, g, esp, serif) {
    police(t, g, esp, serif);
    var w = XR.measureText(s).width;
    if ('letterSpacing' in XR) XR.letterSpacing = '0px';
    return w;
  }
  function libre(x, y, w, h) {
    for (var i = 0; i < BOITES.length; i++) {
      var b = BOITES[i];
      if (Math.abs(x - b[0]) < (w + b[2]) / 2 && Math.abs(y - b[1]) < (h + b[3]) / 2) return false;
    }
    return true;
  }
  function pose(x, y, w, h, jeu) {
    for (var d = 0; d <= jeu; d += 6) {
      if (libre(x, y - d, w, h)) { BOITES.push([x, y - d, w, h]); return y - d; }
      if (d && libre(x, y + d, w, h)) { BOITES.push([x, y + d, w, h]); return y + d; }
    }
    return null;
  }
  function texte(s, x, y, t, g, coul, esp, serif) {
    police(t, g, esp, serif);
    XR.textAlign = 'center';
    XR.lineWidth = 4.5; XR.strokeStyle = rgb(SOL, .85); XR.lineJoin = 'round';
    XR.strokeText(s, x, y);
    XR.fillStyle = coul; XR.fillText(s, x, y);
    if ('letterSpacing' in XR) XR.letterSpacing = '0px';
    XR.textAlign = 'left';
  }

  /* ------------------------------------------------------------ eau ----
     A cette echelle l'Isere fait huit pixels de large : aucune texture ne
     peut s'y lire. On la dessine donc un peu plus large qu'elle n'est, en
     pixels, comme le fait toute carte a petite echelle. */
  var LARG_EAU = { 1: 9, 2: 5, 3: 1.3 };
  var COURS = {
    1: { pas: 640, voies: 2, demi: 26, v: 300, tr: [42, 112, 215] },
    2: { pas: 760, voies: 1, demi: 0,  v: 215, tr: [32, 84, 155] },
    3: { pas: 1150, voies: 1, demi: 0, v: 150, tr: [24, 58, 105] }
  };

  function pointSur(f, d, lat) {
    var c = f.cum, lo = 0, hi = c.length - 1;
    while (lo < hi - 1) { var m = (lo + hi) >> 1; if (c[m] <= d) lo = m; else hi = m; }
    var seg = Math.max(1e-6, c[lo + 1] - c[lo]);
    var u = (d - c[lo]) / seg, i = lo * 3, j = i + 3, p = f.p;
    var e = p[i] + (p[j] - p[i]) * u, n = p[i + 1] + (p[j + 1] - p[i + 1]) * u;
    if (lat) {
      e += -(p[j + 1] - p[i + 1]) / seg * lat;
      n += (p[j] - p[i]) / seg * lat;
    }
    return [e, n, p[i + 2] + (p[j + 2] - p[i + 2]) * u];
  }

  function cheminAxe(rang, zmin, zmax) {
    var vide = true, i, k;
    XR.beginPath();
    for (i = 0; i < PLAN.flots.length; i++) {
      var f = PLAN.flots[i], p = f.p;
      if (f.r !== rang || p[2] < zmin || p[2] >= zmax) continue;
      XR.moveTo(px(p[0], p[1]), py(p[0], p[1], p[2]));
      for (k = 3; k < p.length; k += 3) XR.lineTo(px(p[k], p[k + 1]), py(p[k], p[k + 1], p[k + 2]));
      vide = false;
    }
    return !vide;
  }

  function traceEau(zmin, zmax, t) {
    var i, k, p, rg;
    var surf = false;
    XR.beginPath();
    for (i = 0; i < PLAN.eaux.length; i++) {
      var w = PLAN.eaux[i];
      if (w.z < zmin || w.z >= zmax) continue;
      p = w.p;
      XR.moveTo(px(p[0], p[1]), py(p[0], p[1], w.z));
      for (k = 2; k < p.length; k += 2) XR.lineTo(px(p[k], p[k + 1]), py(p[k], p[k + 1], w.z));
      XR.closePath();
      surf = true;
    }
    if (surf) { XR.fillStyle = rgb(EAU); XR.fill('evenodd'); }

    for (rg = 3; rg >= 1; rg--) {
      if (!cheminAxe(rg, zmin, zmax)) continue;
      if (rg < 3) {
        XR.strokeStyle = rgb(EAU_TRAIT, .5); XR.lineWidth = LARG_EAU[rg] + 1.4; XR.stroke();
      }
      /* Un ruisseau d'un pixel rempli de la couleur du lit disparait : il se
         trace avec la couleur de berge, plus claire. */
      XR.strokeStyle = rg === 3 ? rgb(EAU_TRAIT, .6) : rgb(EAU);
      XR.lineWidth = LARG_EAU[rg];
      XR.stroke();
    }

    /* Le courant : trois lignes qui suivent l'axe et ondulent lateralement,
       en decalage de phase. La surface se froisse au lieu de defiler. */
    if (SOBRE) return;
    for (rg = 1; rg <= 2; rg++) {
      var w2 = LARG_EAU[rg], pas2 = 11 / VUE.s;
      for (var ln = 0; ln < 3; ln++) {
        var amp = w2 * (ln === 1 ? .16 : .32), ph = ln * 2.1, rien = true;
        XR.beginPath();
        for (i = 0; i < PLAN.flots.length; i++) {
          var fl = PLAN.flots[i];
          if (fl.r !== rg || fl.p[2] < zmin || fl.p[2] >= zmax || fl.L < 80) continue;
          var m2 = Math.max(2, Math.floor(fl.L / pas2));
          for (var q = 0; q <= m2; q++) {
            var d2 = q * fl.L / m2, e2 = surEcran(fl, d2);
            var o = amp * Math.sin(d2 / 150 - t * CADENCE * 1.7 + ph)
                  * Math.min(1, d2 / 150, (fl.L - d2) / 150);
            var x2 = e2[0] - e2[3] * o, y2 = e2[1] + e2[2] * o;
            if (q) XR.lineTo(x2, y2); else XR.moveTo(x2, y2);
          }
          rien = false;
        }
        if (!rien) {
          XR.strokeStyle = rgb(COURANT, ln === 1 ? .5 : .34);
          XR.lineWidth = .85; XR.stroke();
        }
      }
    }
  }
  /* Un point de l'axe projete, avec sa tangente : elle donne la
     perpendiculaire ou s'inscrit l'ondulation. */
  function surEcran(f, d) {
    var a = pointSur(f, d, 0), b = pointSur(f, Math.min(f.L, d + 14), 0);
    var ax = px(a[0], a[1]), ay = py(a[0], a[1], a[2]);
    var bx = px(b[0], b[1]), by = py(b[0], b[1], b[2]);
    var l = Math.hypot(bx - ax, by - ay) || 1;
    return [ax, ay, (bx - ax) / l, (by - ay) / l];
  }

  /* --------------------------------------------------------- relief ---- */
  function traceCourbe(c, plein) {
    var p = c.p, k, r;
    if (plein) {
      XR.moveTo(px(p[0], p[1]), py(p[0], p[1], c.z));
      for (k = 2; k < p.length; k += 2) XR.lineTo(px(p[k], p[k + 1]), py(p[k], p[k + 1], c.z));
      XR.closePath();
      return;
    }
    /* Au trait, on saute les portions nees de la fermeture sur le cadre. */
    for (r = 0; r < c.r.length; r++) {
      var a = c.r[r][0], b = c.r[r][1];
      XR.moveTo(px(p[2 * a], p[2 * a + 1]), py(p[2 * a], p[2 * a + 1], c.z));
      for (k = a + 1; k <= b; k++) XR.lineTo(px(p[2 * k], p[2 * k + 1]), py(p[2 * k], p[2 * k + 1], c.z));
    }
  }

  function traceRues(zmin, zmax) {
    /* En gros plan, un trait garde son epaisseur en pixels : la ville se vide
       a mesure qu'on plonge. On l'epaissit donc avec le grossissement. */
    var gros = 1 + VISEE.u * 1.3;
    for (var n = 4; n >= 1; n--) {
      if (n > RUES) continue;
      var vide = true;
      XR.beginPath();
      for (var i = 0; i < PLAN.rues.length; i++) {
        var r = PLAN.rues[i], p = r.p;
        if (r.i !== n || p[2] < zmin || p[2] >= zmax) continue;
        XR.moveTo(px(p[0], p[1]), py(p[0], p[1], p[2]));
        for (var k = 3; k < p.length; k += 3) XR.lineTo(px(p[k], p[k + 1]), py(p[k], p[k + 1], p[k + 2]));
        vide = false;
      }
      if (vide) continue;
      XR.strokeStyle = gris(Math.min(1, TON_RUE[n] * (1 + VISEE.u * .7)));
      XR.lineWidth = LARG_RUE[n] * gros;
      XR.stroke();
    }
  }

  function dessineRelief(t) {
    XR.setTransform(CR.r, 0, 0, CR.r, 0, 0);
    XR.fillStyle = rgb(SOL); XR.fillRect(0, 0, CR.w, CR.h);
    XR.lineCap = 'round'; XR.lineJoin = 'round';

    var C = PLAN.courbes, i = 0, pas = PLAN.pas, zmin = C[0].z;

    traceRues(-1e4, zmin); traceEau(-1e4, zmin, t);
    while (i < C.length) {
      var z = C[i].z, j = i, k;
      XR.beginPath();
      while (j < C.length && C[j].z === z) { if (C[j].f) traceCourbe(C[j], true); j++; }
      XR.fillStyle = rgb(SOL); XR.fill('evenodd');

      /* Le souffle : une onde de densite qui gravit les versants. */
      var forte = (z % 100 === 0), ton = forte ? TON_FORTE : TON_FINE;
      if (!SOBRE) {
        var onde = .5 + .5 * Math.sin(2 * Math.PI * ((z - zmin) / 420 - t * CADENCE / 11));
        ton *= .42 + 1.05 * onde;
      }
      XR.beginPath();
      for (k = i; k < j; k++) traceCourbe(C[k], false);
      XR.strokeStyle = gris(ton);
      XR.lineWidth = (forte ? .85 : .5) * (1 + VISEE.u * 1.1);
      XR.stroke();

      traceRues(z, z + pas); traceEau(z, z + pas, t);
      i = j;
    }

    traceBati(t);

    /* La toponymie s'efface a mesure qu'on plonge : a fort grossissement, ces
       noms designent des choses sorties du cadre. */
    var att = 1 - VISEE.u;
    if (att > .05) {
      BOITES.length = 0;
      if (ECRAN.length) {
        var cy = ECRAN.reduce(function (a, p) { return a + p[1]; }, 0) / ECRAN.length;
        var wc = 0;
        MAISONS.forEach(function (m) { wc = Math.max(wc, largeur(m.court.toUpperCase(), 10.5, 500, 1.1, false)); });
        var hc = (Math.ceil(MAISONS.length / 2) - 1) * 38 + 30;
        BOITES.push([300 - wc / 2 - 8, cy, wc + 44, hc]);
        BOITES.push([CR.w - 300 + wc / 2 + 8, cy, wc + 44, hc]);
      }
      PLAN.massifs.forEach(function (m) {
        var w = largeur(m.nom, 26, 400, 12, true);
        var x = Math.min(Math.max(px(m.e, m.n), w / 2 + 20), CR.w - w / 2 - 20);
        var y = pose(x, py(m.e, m.n, m.z), w, 30, 160);
        if (y !== null) texte(m.nom, x, y, 26, 400, gris(.5 * att), 12, true);
      });
      PLAN.sommets.forEach(function (s) {
        var nom = s.nom.toUpperCase(), w = largeur(nom, 9.5, 500, 1.9, false);
        var x = px(s.e, s.n), y0 = py(s.e, s.n, s.z);
        var y = pose(x, y0 - 13, Math.max(w, 36), 28, 130);
        if (y === null) return;
        XR.beginPath(); XR.moveTo(x - 5, y0); XR.lineTo(x, y0 - 7); XR.lineTo(x + 5, y0);
        XR.strokeStyle = gris(.7 * att); XR.lineWidth = 1.1; XR.stroke();
        texte(nom, x, y, 9.5, 500, gris(.82 * att), 1.9, false);
        texte(s.alt + ' m', x, y0 + 11, 8.5, 400, gris(.55 * att), .5, false);
      });
      PLAN.hydro.forEach(function (h) {
        var nom = h.nom.toUpperCase(), w = largeur(nom, 10, 400, 3.4, false);
        var x = px(h.e, h.n), y = pose(x, py(h.e, h.n, h.z), w, 22, 140);
        if (y !== null) texte(nom, x, y, 10, 400, rgb(EAU_TRAIT, att), 3.4, false);
      });
    }

    /* La vignette, en dernier et par-dessus, comme le hero pose la sienne sur
       sa video. Elle ne doit jamais teinter les plaques elles-memes, sans quoi
       leurs bords droits se detacheraient du fond. */
    var v = XR.createRadialGradient(CR.w * .5, CR.h * .5, Math.min(CR.w, CR.h) * .25,
                                    CR.w * .5, CR.h * .5, Math.max(CR.w, CR.h) * .72);
    v.addColorStop(0, 'rgba(0,0,0,0)');
    v.addColorStop(1, VIGNETTE);
    XR.fillStyle = v; XR.fillRect(0, 0, CR.w, CR.h);
  }

  /* ----------------------------------------------------------- bati ----
     Vu de loin, une ville se lit a ses rues ; vu de pres, a son bati. Les
     batiments ne sont telecharges qu'a la premiere plongee, et seulement dans
     un disque autour de chaque maison : toute l'emprise pesait un megaoctet.
     Le fondu du bord se fait par anneaux de distance plutot que batiment par
     batiment, pour garder des traces groupes. */
  var ANNEAUX = [[355, 430, .3], [250, 355, .68], [0, 250, 1]];
  var DALLE = 60, LUM_E = -.55, LUM_N = .84;
  var LOT = null, LOT_I = null, LOT_AZ = 1e9, LOT_MAISON = null;

  function chargeBati() {
    if (BATI || BATI_EN_COURS) return;
    BATI_EN_COURS = true;
    fetch(BATI_URL).then(function (r) { return r.json(); }).then(function (d) {
      /* Les deux jeux n'ont pas la meme origine : on ramene le bati dans le
         repere de la carte une fois pour toutes. */
      var K = Math.cos(d.o[1] * Math.PI / 180);
      var ox = (d.o[0] - PLAN.o[0]) * 111320 * K, oy = (d.o[1] - PLAN.o[1]) * 111320;
      d.b.forEach(function (b) {
        var p = b.p, aire = 0, k;
        for (k = 0; k < p.length; k += 2) { p[k] += ox; p[k + 1] += oy; }
        for (k = 0; k < p.length; k += 2) {
          var j = (k + 2) % p.length;
          aire += p[k] * p[j + 1] - p[j] * p[k + 1];
        }
        /* Le sens de parcours decide de quel cote est l'exterieur, donc quels
           murs sont vus. La rotation le conserve : on le calcule une fois. */
        b.sens = aire >= 0 ? 1 : -1;
        if (b.z === null || b.z === undefined) b.z = PLAN.z0;
      });
      BATI = d.b;
      rendu(HORLOGE);
    })['catch'](function () { BATI_EN_COURS = false; });
  }

  function ancreAuCentre() {
    var best = 0, bd = 1e18;
    for (var i = 0; i < MAISONS.length; i++) {
      var dx = ECRAN[i][0] - CR.w / 2, dy = ECRAN[i][1] - CR.h / 2, d = dx * dx + dy * dy;
      if (d < bd) { bd = d; best = i; }
    }
    return best;
  }

  /* Le tri et la selection ne changent pas d'une image a l'autre : la maison
     visee est la meme, et l'azimut ne bouge que d'un demi-degre par seconde.
     Les refaire soixante fois par seconde coutait sept millisecondes. */
  function lotBati(vise, ancre) {
    if (LOT_I !== ancre) {
      LOT_I = ancre;
      LOT = [[], [], []];
      for (var i = 0; i < BATI.length; i++) {
        var b = BATI[i], dx = b.p[0] - vise.e, dy = b.p[1] - vise.n;
        var d = Math.sqrt(dx * dx + dy * dy);
        for (var a = 0; a < ANNEAUX.length; a++)
          if (d >= ANNEAUX[a][0] && d < ANNEAUX[a][1]) { LOT[a].push(b); break; }
      }
      /* Le batiment de la maison. Surtout pas celui qui CONTIENT le point :
         les adresses sont geocodees sur la voie, aucune des maisons ne tombe
         dans un batiment, le plus proche etant entre dix et quarante metres.
         On prend donc le plus proche, mesure sur les sommets et non sur le
         centre, un grand immeuble ayant son centre loin de sa facade. */
      LOT_MAISON = null;
      var mieux = 1e18;
      for (var q = 0; q < LOT[2].length; q++) {
        var c = LOT[2][q], P = c.p, dd = 1e18;
        for (var u = 0; u < P.length; u += 2) {
          var ee = P[u] - vise.e, nn = P[u + 1] - vise.n, t2 = ee * ee + nn * nn;
          if (t2 < dd) dd = t2;
        }
        if (dd < mieux) { mieux = dd; LOT_MAISON = c; }
      }
      LOT_AZ = 1e9;
    }
    if (Math.abs(VUE.az - LOT_AZ) > 1.5) {
      LOT_AZ = VUE.az;
      LOT.forEach(function (r) {
        r.forEach(function (b) { b.prof = -b.p[0] * VUE.sa + b.p[1] * VUE.ca; });
        r.sort(function (u, v) { return v.prof - u.prof; });   // du fond vers l'avant
      });
    }
    return LOT;
  }

  function traceBati(t) {
    if (!BATI || !MAISONS.length) return;
    /* Trop haut, les batiments ne sont plus que des grains. */
    if (VUE.zoomEff < 4) return;
    var ancre;
    if (VISEE.i !== null && VISEE.u > .05)
      ancre = (VISEE.de !== null && VISEE.q < .5) ? VISEE.de : VISEE.i;
    else
      ancre = ancreAuCentre();
    var vise = MAISONS[ancre];
    if (!vise) return;
    var vue = Math.min(1, (VUE.zoomEff - 4) / 2.5);
    var murs = [[], [], []], toits = [];

    function vide(al) {
      for (var m = 0; m < 3; m++) {
        if (!murs[m].length) continue;
        XR.beginPath();
        for (var q = 0; q < murs[m].length; q += 8) {
          var w = murs[m];
          XR.moveTo(w[q], w[q + 1]); XR.lineTo(w[q + 2], w[q + 3]);
          XR.lineTo(w[q + 4], w[q + 5]); XR.lineTo(w[q + 6], w[q + 7]);
          XR.closePath();
        }
        XR.fillStyle = gris(TON_MUR[m], al); XR.fill();
        murs[m].length = 0;
      }
      if (toits.length) {
        XR.beginPath();
        for (var r = 0; r < toits.length; r++) {
          var tt = toits[r];
          XR.moveTo(tt[0], tt[1]);
          for (var k = 2; k < tt.length; k += 2) XR.lineTo(tt[k], tt[k + 1]);
          XR.closePath();
        }
        /* Les toits prennent la couleur du fond, cernes d'un trait, comme les
           plaques de courbes : remplis d'un gris franc, le quartier tournait
           au tapis uniforme. */
        XR.fillStyle = gris(TON_TOIT, al); XR.fill();
        XR.strokeStyle = gris(TON_ARETE, al); XR.lineWidth = .65; XR.stroke();
        toits.length = 0;
      }
    }

    var lots = lotBati(vise, ancre);
    for (var a = 0; a < ANNEAUX.length; a++) {
      var al = ANNEAUX[a][2] * vue, lot = lots[a];
      if (!lot.length) continue;
      for (var j = 0; j < lot.length; j++) {
        var bt = lot[j], P = bt.p, dh = bt.h * RELIEF * VUE.s, toit = [];
        for (var k = 0; k < P.length; k += 2) {
          var k2 = (k + 2) % P.length;
          var ax = px(P[k], P[k + 1]), bx = px(P[k2], P[k2 + 1]);
          var ay = py(P[k], P[k + 1], bt.z);
          toit.push(ax, ay - dh);
          /* Mur de dos : dans cette projection, la visibilite d'un pan
             vertical ne depend que du sens ou file son arete a l'ecran. */
          if (bt.sens * (bx - ax) <= 0) continue;
          var by = py(P[k2], P[k2 + 1], bt.z);
          var de = P[k2] - P[k], dn = P[k2 + 1] - P[k + 1];
          var l = Math.hypot(de, dn) || 1;
          var e = bt.sens * (dn * LUM_E - de * LUM_N) / l;
          murs[e < -.3 ? 0 : e < .35 ? 1 : 2].push(ax, ay, bx, by, bx, by - dh, ax, ay - dh);
        }
        toits.push(toit);
        if ((j + 1) % DALLE === 0) vide(al);
      }
      vide(al);
    }

    /* La maison, repeinte par-dessus : c'est elle qu'on est venu voir. Elle
       respire lentement, la ou les jalons battent comme un coeur. */
    if (LOT_MAISON) {
      var souffle = SOBRE ? .5 : .5 + .5 * Math.sin(t * 2.5);
      var m = LOT_MAISON, Q = m.p, dm = m.h * RELIEF * VUE.s, hau = [];
      XR.beginPath();
      for (var g = 0; g < Q.length; g += 2) {
        var g2 = (g + 2) % Q.length;
        var gx = px(Q[g], Q[g + 1]), gy = py(Q[g], Q[g + 1], m.z);
        hau.push(gx, gy - dm);
        if (m.sens * (px(Q[g2], Q[g2 + 1]) - gx) <= 0) continue;
        var hx = px(Q[g2], Q[g2 + 1]), hy = py(Q[g2], Q[g2 + 1], m.z);
        XR.moveTo(gx, gy); XR.lineTo(hx, hy);
        XR.lineTo(hx, hy - dm); XR.lineTo(gx, gy - dm); XR.closePath();
      }
      XR.fillStyle = rgb(TERRA, (.33 + .27 * souffle) * vue); XR.fill();
      XR.beginPath();
      XR.moveTo(hau[0], hau[1]);
      for (var g3 = 2; g3 < hau.length; g3 += 2) XR.lineTo(hau[g3], hau[g3 + 1]);
      XR.closePath();
      XR.fillStyle = rgb(TERRA, (.17 + .22 * souffle) * vue); XR.fill();
      XR.strokeStyle = rgb(TERRA, (.7 + .3 * souffle) * vue);
      XR.lineWidth = 1.2 + .7 * souffle; XR.stroke();
    }
  }

  /* --------------------------------------------------------- jalons ----
     Un piquet plante dans le terrain, comme une borne de geometre. Il bat :
     deux poussees rapprochees puis un repos, comme un coeur. Une sinusoide
     donnerait une respiration, pas une pulsation. */
  function battement(u) {
    return Math.exp(-Math.pow((u - .05) / .062, 2))
         + .58 * Math.exp(-Math.pow((u - .25) / .08, 2));
  }

  function dessineJalons(t) {
    XN.setTransform(CN.r, 0, 0, CN.r, 0, 0);
    XN.clearRect(0, 0, CN.w, CN.h);
    if (!MAISONS.length) return;
    ECRAN = MAISONS.map(function (m) { return [px(m.e, m.n), py(m.e, m.n, m.z)]; });

    var gros = 1 + VISEE.u * .9;
    /* Au ras des toits le jalon fait doublon avec le batiment, qui est la, en
       terre cuite. On l'efface entre trois et six fois le grossissement. */
    var presence = 1 - Math.min(1, Math.max(0, (VUE.zoomEff - 3) / 3));
    if (presence <= .01) return;
    var apl = Math.max(.08, PENTE);

    for (var i = 0; i < MAISONS.length; i++) {
      var p = ECRAN[i];
      var vu = (VISEE.i === i), autre = (VISEE.i !== null && !vu);
      var att = (autre ? (1 - VISEE.u * .82) : 1) * presence;
      var actif = vu || VISEE.survol === i;
      /* Chaque maison bat a son propre temps : ensemble, ce serait un
         clignotant. */
      var u = ((t / PERIODE) + i * .17) % 1;
      var bat = SOBRE ? 0 : battement(u) * (actif ? 1.25 : 1);

      var H = 24 * gros;
      XN.strokeStyle = rgb(TERRA, .75 * att); XN.lineWidth = 1.5 * gros;
      XN.beginPath(); XN.moveTo(p[0], p[1]); XN.lineTo(p[0], p[1] - H); XN.stroke();
      /* Un cercle pose a plat sur le terrain devient une ellipse dont le petit
         axe vaut le grand multiplie par l'inclinaison, et cela ne depend pas
         de l'azimut : une rotation laisse le cercle sur lui-meme. */
      XN.beginPath();
      XN.ellipse(p[0], p[1], (4 + 2 * bat) * gros,
                 Math.max(.8, (4 + 2 * bat) * gros * apl), 0, 0, 6.2832);
      XN.strokeStyle = rgb(TERRA, .5 * att); XN.lineWidth = 1.1; XN.stroke();

      var rt = (5 + 1.7 * bat) * gros;
      XN.beginPath(); XN.arc(p[0], p[1] - H, rt, 0, 6.2832);
      XN.fillStyle = rgb(SOL, .9 * att + .1); XN.fill();
      XN.strokeStyle = rgb(TERRA, att); XN.lineWidth = (actif ? 2.9 : 2.3) * gros; XN.stroke();
      XN.beginPath(); XN.arc(p[0], p[1] - H, rt * .38, 0, 6.2832);
      XN.fillStyle = rgb(TERRA, att); XN.fill();

      if (VISEE.survol === i && !vu) {
        XN.font = "500 12px 'Inter',system-ui,sans-serif";
        if ('letterSpacing' in XN) XN.letterSpacing = '.6px';
        var nom = MAISONS[i].court, w = XN.measureText(nom).width;
        var lx = p[0] + 14, ali = 'left';
        if (lx + w > CN.w - 12) { lx = p[0] - 14; ali = 'right'; }
        XN.textAlign = ali;
        XN.lineWidth = 5; XN.strokeStyle = rgb(SOL, .88); XN.lineJoin = 'round';
        XN.strokeText(nom, lx, p[1] + 4.2 - 30 * gros);
        XN.fillStyle = gris(.97); XN.fillText(nom, lx, p[1] + 4.2 - 30 * gros);
        XN.textAlign = 'left';
        if ('letterSpacing' in XN) XN.letterSpacing = '0px';
      }
    }
  }

  /* -------------------------------------------------------- la fiche ---
     Frappee ligne apres ligne, un caractere toutes les 18 a 44 ms : en
     dessous on ne lit plus une frappe, au-dessus on s'impatiente. */
  var LIGNES = ['mjc-f-nom', 'mjc-f-quartier', 'mjc-f-adresse', 'mjc-f-tel'];

  function nu(t) {
    return t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]/g, '');
  }
  /* Le quartier ne doit pas redire le nom : « Lucie Aubrac (Clos d'Or) »
     suivi de « Clos d'Or » est une redite. */
  function quartierUtile(nom, quartier) {
    if (!quartier) return '';
    var cle = nu(nom);
    return quartier.split('/').map(function (p) { return p.trim(); })
      .filter(function (p) { return p && cle.indexOf(nu(p)) < 0; }).join(' / ');
  }

  function frappe(m) {
    clearTimeout(MACHINE);
    var txt = [m.nom, quartierUtile(m.nom, m.quartier), m.adresse, m.tel];
    LIGNES.forEach(function (id) {
      var el = document.getElementById(id);
      if (el) { el.textContent = ''; el.classList.remove('tape'); }
    });
    if (SOBRE) {
      LIGNES.forEach(function (id, k) {
        var el = document.getElementById(id);
        if (el) el.textContent = txt[k];
      });
      return;
    }
    var li = 0, ci = 0;
    (function pas() {
      while (li < LIGNES.length && !txt[li]) li++;
      if (li >= LIGNES.length) return;
      var el = document.getElementById(LIGNES[li]);
      if (!el) return;
      el.classList.add('tape');
      el.textContent = txt[li].slice(0, ++ci);
      if (ci >= txt[li].length) {
        el.classList.remove('tape');
        li++; ci = 0;
        MACHINE = setTimeout(pas, 210);
      } else {
        MACHINE = setTimeout(pas, 18 + Math.random() * 26);
      }
    })();
  }

  function vise(i, fort) {
    if (!MAISONS[i]) return;
    chargeBati();
    remetVue();
    VISEE.zbut = fort ? ZOOM_FORT : ZOOM;
    if (VISEE.i === i) { VISEE.but = 1; return; }
    if (VISEE.i !== null && VISEE.p > .55) {
      /* Deja en gros plan ailleurs : on y va en volant, d'autant plus
         longtemps que la distance est grande. */
      var a = MAISONS[VISEE.i], b = MAISONS[i];
      VISEE.de = VISEE.i; VISEE.q = 0;
      VISEE.duree = Math.min(4.2, 1.2 + Math.hypot(b.e - a.e, b.n - a.n) / 1000);
      clearTimeout(MACHINE);
      LIGNES.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { el.textContent = ''; el.classList.remove('tape'); }
      });
    } else {
      VISEE.de = null; VISEE.q = 1;
      frappe(MAISONS[i]);
    }
    VISEE.i = i; VISEE.but = 1;
    if (FICHE) FICHE.classList.add('ouverte');
    MAISONS.forEach(function (m, k) { m.el.classList.toggle('mjc-item-vise', k === i); });
  }

  function relache() {
    if (VISEE.i === null) return;
    VISEE.but = 0; VISEE.de = null; VISEE.q = 1;
    VISEE.zc = ZOOM; VISEE.zbut = ZOOM;
    if (FICHE) FICHE.classList.remove('ouverte');
    clearTimeout(MACHINE);
    MAISONS.forEach(function (m) { m.el.classList.remove('mjc-item-vise'); });
  }

  function remetVue() { VUE.zm = 1; VUE.panx = 0; VUE.pany = 0; }

  /* --------------------------------------------------------- pilote ---- */
  function taille() {
    var l = SC.clientWidth, h = Math.round(Math.min(Math.max(l * .62, 320), 640));
    var r = window.devicePixelRatio || 1;
    [CR, CN].forEach(function (c) {
      c.w = l; c.h = h; c.r = r;
      c.width = Math.round(l * r); c.height = Math.round(h * r);
      c.style.height = h + 'px';
    });
  }
  function rendu(t) { angles(); cadre(); dessineRelief(t || 0); dessineJalons(t || 0); }

  function local(e) {
    var b = SC.getBoundingClientRect();
    return [e.clientX - b.left, e.clientY - b.top];
  }
  function maisonSous(x, y) {
    var best = null, bd = 26 * 26;
    for (var i = 0; i < ECRAN.length; i++) {
      var dx = ECRAN[i][0] - x, dy = ECRAN[i][1] - y - 24, d = dx * dx + dy * dy;
      if (d < bd) { bd = d; best = i; }
    }
    return best;
  }

  /* Tirer fait tourner, et rien d'autre : faire varier l'azimut et
     l'inclinaison du meme geste rend la vue impossible a poser. */
  var tire = null;
  function branche() {
    SC.addEventListener('pointerdown', function (e) {
      if (!PLAN) return;
      tire = { x: e.clientX, y: e.clientY, az: VUE.az, bouge: 0,
               px: VUE.panx, py: VUE.pany, deplace: e.shiftKey || e.button === 2 };
      SC.classList.add('mjc-carte-tire');
      if (SC.setPointerCapture) SC.setPointerCapture(e.pointerId);
    });
    SC.addEventListener('pointermove', function (e) {
      if (!PLAN) return;
      if (!tire) {
        var p = local(e), sur = maisonSous(p[0], p[1]);
        if (sur !== VISEE.survol) {
          VISEE.survol = sur;
          SC.style.cursor = sur === null ? '' : 'pointer';
          dessineJalons(HORLOGE);
        }
        return;
      }
      tire.bouge = Math.max(tire.bouge, Math.abs(e.clientX - tire.x) + Math.abs(e.clientY - tire.y));
      if (tire.deplace) {
        VUE.panx = tire.px + (e.clientX - tire.x);
        VUE.pany = tire.py + (e.clientY - tire.y);
      } else {
        VUE.az = Math.max(-180, Math.min(180, tire.az + (e.clientX - tire.x) * .3));
      }
      rendu(HORLOGE);
    });
    SC.addEventListener('pointerup', function (e) {
      var clic = tire && tire.bouge < 5;
      tire = null; SC.classList.remove('mjc-carte-tire');
      if (!clic || !PLAN) return;
      var p = local(e), sur = maisonSous(p[0], p[1]);
      if (sur !== null) vise(sur); else relache();
    });
    SC.addEventListener('pointercancel', function () { tire = null; SC.classList.remove('mjc-carte-tire'); });
    SC.addEventListener('pointerleave', function () {
      if (VISEE.survol !== null) { VISEE.survol = null; SC.style.cursor = ''; dessineJalons(HORLOGE); }
    });
    SC.addEventListener('contextmenu', function (e) { e.preventDefault(); });
    /* Double clic : on descend au ras du batiment. */
    SC.addEventListener('dblclick', function (e) {
      if (!PLAN) return;
      var p = local(e), sur = maisonSous(p[0], p[1]);
      if (sur !== null) vise(sur, true);
      else if (VISEE.i !== null) vise(VISEE.i, true);
    });
    /* La molette grossit vers le curseur. */
    SC.addEventListener('wheel', function (e) {
      if (!PLAN) return;
      e.preventDefault();
      var p = local(e);
      var ax = (p[0] - VUE.dx) / VUE.s, ay = (p[1] - VUE.dy) / VUE.s;
      VUE.zm = Math.max(1, Math.min(40, VUE.zm * Math.pow(1.0016, -e.deltaY)));
      if (VUE.zm > 1.001) chargeBati();
      angles(); cadre();
      VUE.panx += p[0] - (ax * VUE.s + VUE.dx);
      VUE.pany += p[1] - (ay * VUE.s + VUE.dy);
      if (VUE.zm === 1) { VUE.panx = 0; VUE.pany = 0; }
      rendu(HORLOGE);
    }, { passive: false });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { relache(); remetVue(); rendu(HORLOGE); }
    });
    window.addEventListener('resize', function () { if (PLAN) { taille(); rendu(HORLOGE); } });

    /* Cliquer une fiche revient a cliquer son jalon. */
    MAISONS.forEach(function (m, i) {
      m.el.addEventListener('click', function (ev) {
        if (ev.target.closest('a')) return;   // les liens de la fiche restent des liens
        vise(i);
        SC.scrollIntoView({ behavior: SOBRE ? 'auto' : 'smooth', block: 'center' });
      });
    });

    var f = document.getElementById('mjc-fiche-fermer');
    if (f) f.addEventListener('click', function () { relache(); remetVue(); rendu(HORLOGE); });
  }

  /* La boucle. Elle s'arrete quand la page passe en arriere-plan ou quand le
     dessin sort de l'ecran : une carte qui tourne toute seule ne doit pas
     faire tourner le ventilateur d'un portable pendant que personne ne la
     regarde. */
  function boucle(ms) {
    requestAnimationFrame(boucle);
    if (!PLAN) return;
    if (!MS0) MS0 = ms;
    HORLOGE = (ms - MS0) / 1000;
    var dt = Math.min(.1, HORLOGE - PREC);
    PREC = HORLOGE;
    if (!VISIBLE || document.hidden) return;

    var bouge = !SOBRE || (BATI && VUE.zoomEff >= 4);

    if (Math.abs(VISEE.zc - VISEE.zbut) > .02) {
      VISEE.zc += (VISEE.zbut - VISEE.zc) * Math.min(1, dt * 3.2);
      bouge = true;
    }
    if (VISEE.de !== null && VISEE.q < 1) {
      VISEE.q = Math.min(1, VISEE.q + dt / VISEE.duree);
      if (VISEE.q >= 1) { VISEE.de = null; frappe(MAISONS[VISEE.i]); }
      bouge = true;
    }
    if (VISEE.p !== VISEE.but) {
      VISEE.p += (VISEE.but > VISEE.p ? dt / 1.5 : -dt / 1.0);
      VISEE.p = Math.max(0, Math.min(1, VISEE.p));
      VISEE.u = douceur(VISEE.p);
      if (VISEE.p === 0) VISEE.i = null;
      bouge = true;
    }
    if (!tire && TOUR > 0 && !SOBRE) {
      VUE.az += TOUR * dt;
      while (VUE.az > 180) VUE.az -= 360;
      bouge = true;
    }
    if (!tire && bouge) { angles(); cadre(); dessineRelief(HORLOGE); }
    dessineJalons(HORLOGE);
  }

  /* ------------------------------------------------------- demarrage --- */
  function altitudeDe(lon, lat) {
    var g = PLAN.grille, c = PLAN.cadre, n = g.n;
    var i = Math.round((lon - c[0]) / (c[1] - c[0]) * (n - 1));
    var j = Math.round((c[3] - lat) / (c[3] - c[2]) * (n - 1));
    i = Math.max(0, Math.min(n - 1, i)); j = Math.max(0, Math.min(n - 1, j));
    return g.z[j * n + i];
  }

  /* Les maisons viennent de la page, donc du back-office. */
  function lisMaisons() {
    var K = Math.cos(PLAN.o[1] * Math.PI / 180);
    var items = [].slice.call(document.querySelectorAll('.mjc-item'));
    MAISONS = [];
    items.forEach(function (el) {
      var pts;
      try { pts = JSON.parse(el.getAttribute('data-points') || '[]'); } catch (x) { return; }
      if (!pts.length || !pts[0].lat || !pts[0].lon) return;
      var p = pts[0];
      var nom = (el.querySelector('h2') || {}).textContent || '';
      var q = el.querySelector('.mjc-quartier');
      var a = el.querySelector('.mjc-address');
      var tel = el.querySelector('.mjc-contact a[href^="tel:"]');
      MAISONS.push({
        el: el,
        nom: nom.trim(),
        court: nom.replace(/^MJC\s+/, '').replace(/^Maison Pour Tous/i, 'MPT').trim(),
        quartier: q ? q.textContent.trim() : '',
        adresse: a ? a.childNodes[0].textContent.trim().replace(/\s+/g, ' ') : '',
        tel: tel ? tel.textContent.trim() : '',
        e: (p.lon - PLAN.o[0]) * 111320 * K,
        n: (p.lat - PLAN.o[1]) * 111320,
        z: altitudeDe(p.lon, p.lat)
      });
    });
  }

  function demarre(d) {
    PLAN = d;
    PLAN.courbes.sort(function (a, b) { return a.z - b.z; });
    var niv = [];
    PLAN.courbes.forEach(function (c) { if (niv.indexOf(c.z) < 0) niv.push(c.z); });
    PLAN.pas = niv.length > 1 ? niv[1] - niv[0] : 50;

    /* Longueurs cumulees des axes d'ecoulement : metriques, donc independantes
       de l'angle de vue. Une seule fois au chargement. */
    PLAN.flots.forEach(function (f) {
      var c = [0], L = 0, p = f.p;
      for (var k = 3; k < p.length; k += 3) {
        L += Math.hypot(p[k] - p[k - 3], p[k + 1] - p[k - 2]);
        c.push(L);
      }
      f.cum = c; f.L = L;
    });

    ECH = [];
    PLAN.courbes.forEach(function (c) {
      for (var k = 0; k < c.p.length; k += 12) ECH.push(c.p[k], c.p[k + 1], c.z);
    });

    lisMaisons();
    FICHE = document.getElementById('mjc-fiche');
    taille(); rendu(0);
    if (document.fonts && document.fonts.ready)
      document.fonts.ready.then(function () { rendu(HORLOGE); });
    branche();
    if (window.IntersectionObserver)
      new IntersectionObserver(function (e) { VISIBLE = e[0].isIntersecting; },
                               { threshold: .02 }).observe(SC);
    requestAnimationFrame(boucle);
    SC.classList.add('mjc-carte-prete');
  }

  CR = document.createElement('canvas');
  CN = document.createElement('canvas');
  CR.className = 'mjc-carte-relief';
  CN.className = 'mjc-carte-jalons';
  SC.appendChild(CR); SC.appendChild(CN);
  XR = CR.getContext('2d'); XN = CN.getContext('2d');

  fetch(CARTE).then(function (r) { return r.json(); }).then(demarre)['catch'](function () {
    /* Sans la carte, la page garde sa liste : on retire simplement le cadre. */
    SC.hidden = true;
  });
})();
