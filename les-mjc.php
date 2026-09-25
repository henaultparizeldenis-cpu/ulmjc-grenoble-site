<?php
/* Page publique : Les MJC. La liste des maisons vient du back-office
   (admin/mjc.php) via load_items('mjc') : en ajouter ou en retirer une se
   fait dans l'outil, et se répercute ici comme sur la carte, qui lit le
   même fichier. */
require_once __DIR__ . '/inc/lib.php';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Les MJC et Maison Pour Tous membres de l'Union Locale des MJC de Grenoble : Parmentier, Eaux Claires, Lucie Aubrac, Abbaye, Mutualité, Anatole France et MPT Saint-Laurent.">
<title>Les MJC/MPT | ULMJC Grenoble</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="canonical" href="https://site.ulmjcgrenoble.org/les-mjc.php">
<link rel="stylesheet" href="css/style.css?v=20260924-11">
<!-- Matomo Analytics - mode anonyme (sans cookies, IP anonymisee) -->
<script>
  var _paq = window._paq = window._paq || [];
  _paq.push(['disableCookies']);
  _paq.push(['setDoNotTrack', true]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u = "//stats.ulmjcgrenoble.org/matomo/";
    _paq.push(['setTrackerUrl', u + 'matomo.php']);
    _paq.push(['setSiteId', '1']);
    var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
    g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
  })();
</script>
<!-- End Matomo -->
</head>
<body>

<?php $active = 'mjc'; include __DIR__ . '/inc/nav.php'; ?>

<div class="page-header">
  <div class="container">
    <span class="section-eyebrow">Notre réseau</span>
    <h1>Les Maisons des Jeunes et de la Culture et Maison Pour Tous de Grenoble.</h1>
    <?php
    /* Le nombre de maisons suit les donnees : il etait ecrit en toutes lettres
       dans la page, et se trompait des qu'on en ajoutait une. On compte les
       noms distincts, une maison a deux adresses restant une maison. */
    $noms = array();
    foreach (array_filter(active_items('mjc'), function ($m) { return !empty($m['published']); }) as $m) {
      $base = trim(preg_replace('/\s*\(.*$/u', '', $m['nom'] ?? ''));
      if ($base !== '') $noms[$base] = true;
    }
    $n = count($noms);
    $lettres = array(1 => 'Une', 'Deux', 'Trois', 'Quatre', 'Cinq', 'Six', 'Sept',
                     'Huit', 'Neuf', 'Dix', 'Onze', 'Douze', 'Treize', 'Quatorze', 'Quinze');
    $combien = isset($lettres[$n]) ? $lettres[$n] : $n;
    ?>
    <p class="lede"><?= e($combien) ?> maison<?= $n > 1 ? 's' : '' ?> de quartier, chacune avec son identité, ses adhérents, ses projets. Ensemble, elles forment l'Union Locale et font vivre l'éducation populaire à Grenoble.</p>
  </div>
</div>

<section>
  <div class="container">
    <p class="muted center" style="max-width:640px;margin:0 auto 2rem;">
      Toutes les maisons sont ouvertes à <em>tout le monde</em>, quel que soit
      le quartier où l'on habite.
    </p>

    <?php /* La carte se dessine dans ce cadre (js/carte-mjc.js). Elle lit les
             maisons sur les attributs data-points des fiches ci-dessous : une
             maison ajoutée dans le back-office y apparaît sans rien d'autre.
             Sans JavaScript, le cadre reste vide et la liste suffit. */ ?>
    <div id="mjc-carte" class="mjc-carte" role="img"
         aria-label="Carte en relief de Grenoble situant les maisons de l'union">
      <div class="mjc-fiche" id="mjc-fiche" aria-live="polite">
        <div class="mjc-fiche-tete">
          <h2 id="mjc-f-nom"></h2>
          <button class="mjc-fiche-fermer" id="mjc-fiche-fermer" type="button">Revenir</button>
        </div>
        <p class="mjc-f-quartier" id="mjc-f-quartier"></p>
        <p id="mjc-f-adresse"></p>
        <p class="mjc-f-tel" id="mjc-f-tel"></p>
      </div>
    </div>
    <p class="mjc-carte-aide muted center">
      Cliquez une maison, sur la carte ou dans la liste, pour y descendre.
      Tirez pour tourner autour de la vallée, la molette grossit.
      La liste ci-dessous donne les mêmes informations, sous forme de texte.
    </p>

    <?php
    /* Les maisons viennent désormais du back-office (admin/mjc.php) et non
       plus du code : en ajouter ou en retirer une se fait dans l'outil, et se
       répercute ici comme sur la carte, qui lit le même fichier.

       data-points garde la forme d'un tableau : une maison peut avoir
       plusieurs points, comme Lucie Aubrac qui tient deux adresses. */
    $maisons = array_filter(active_items('mjc'), function ($m) { return !empty($m['published']); });
    usort($maisons, 'cmp_ordre');
    ?>
    <ul class="mjc-list reveal-stagger">
<?php foreach ($maisons as $m):
      $points = array();
      if (is_numeric($m['lat'] ?? null) && is_numeric($m['lon'] ?? null)) {
        $pt = array('lat' => (float)$m['lat'], 'lon' => (float)$m['lon']);
        if (!empty($m['lieu'])) $pt['lieu'] = $m['lieu'];
        $points[] = $pt;
      }
      $logo = mjc_logo_src($m['logo'] ?? '');
      $nom  = $m['nom'] ?? '';
?>
      <li class="mjc-item" data-points='<?= e(json_encode($points, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>
        <div class="mjc-logo">
          <?php if ($logo !== ''): ?>
            <img src="<?= e($logo) ?>" alt="Logo <?= e($nom) ?>" onerror="this.parentNode.classList.add('mjc-logo-placeholder');this.parentNode.innerHTML='<span><?= e($nom) ?></span>'">
          <?php else: ?>
            <span><?= e($nom) ?></span>
          <?php endif; ?>
        </div>
        <div class="mjc-info">
          <h2><?= e($nom) ?></h2>
          <?php if (!empty($m['quartier'])): ?><p class="mjc-quartier"><?= e($m['quartier']) ?></p><?php endif; ?>
          <?php if (!empty($m['adresse'])): ?>
          <p class="mjc-address">
            <?= e($m['adresse']) ?><?php if (!empty($m['antennes'])): ?><br>
            <span class="muted">Antennes&nbsp;: <?= e($m['antennes']) ?></span><?php endif; ?>
          </p>
          <?php endif; ?>
          <?php if (!empty($m['tel']) || !empty($m['email'])): ?>
          <p class="mjc-contact">
            <?php if (!empty($m['tel'])): ?>
            &#128222; <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $m['tel'])) ?>"><?= e($m['tel']) ?></a><?php if (!empty($m['email'])): ?><br><?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($m['email'])): ?>
            &#9993;&#65039; <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>
            <?php endif; ?>
          </p>
          <?php endif; ?>
          <?php if (!empty($m['site'])): ?>
          <p class="mjc-link">
            <a href="<?= e($m['site']) ?>" target="_blank" rel="noopener">Visiter le site &#8599;</a>
          </p>
          <?php endif; ?>
        </div>
      </li>
<?php endforeach; ?>
    </ul>

    <p class="muted center" style="margin-top: 3rem; font-size: 0.95rem;">
      <em>Les MJC affichées sans logo le seront prochainement, dès que nous aurons récupéré leurs visuels officiels.</em>
    </p>
  </div>
</section>

<section style="background: var(--bg-soft);">
  <div class="container center reveal">
    <span class="section-eyebrow">Rejoindre l'union</span>
    <h2>Poussez la porte de la MJC de votre quartier.</h2>
    <p style="max-width: 620px; margin: 0 auto 2rem; color: var(--ink-soft);">
      Chaque MJC accueille tout au long de l'année, pour s'inscrire à une activité,
      proposer un projet, ou simplement passer dire bonjour. L'adhésion est solidaire
      et donne accès à toute la programmation locale.
    </p>
    <a href="contact.php" class="btn">Nous contacter</a>
  </div>
</section>

<script src="js/carte-mjc.js?v=20260925-1"></script>
<?php include __DIR__ . '/inc/site-footer.php'; ?>

<script src="js/main.js?v=20260524-14"></script>

</body>
</html>
