<?php
/* En-tête public ULMJC (head + header/nav) — extrait de index.html du site
   ULMJC, transformé en include réutilisable pour les pages PHP du CMS.
   Chaque page définit $page_title / $page_desc / $page_active avant l'include. */
require_once __DIR__ . '/lib.php';
$page_title  = isset($page_title)  ? $page_title  : 'ULMJC Grenoble';
$page_desc   = isset($page_desc)   ? $page_desc   : "Union Locale des MJC de Grenoble — éducation populaire depuis 1961.";
$page_active = isset($page_active) ? $page_active : ''; // clé de nav active (ex. 'actus')
$v = ASSET_V;

// Aucune mise en cache des pages publiques : on sert toujours la version à jour.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Lien actif dans la nav
$nav = array(
  'index.html'         => array('Accueil', ''),
  'asso.html'          => array('Asso', 'asso'),
  'les-mjc.html'       => array('MJC', 'mjc'),
  'chalet.php'         => array('Chalet', 'chalet'),
  'activites.php'      => array('Activités', 'activites'),
  'actus.php'          => array('Actualités', 'actus'),
  'partenariats.php'   => array('Partenaires', 'partenaires'),
  'contact.html'       => array('Contact', 'contact'),
);
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= e($page_desc) ?>">
<title><?= e($page_title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css?v=<?= e($v) ?>">
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

<header class="site-header">
  <div class="container nav">
    <a href="index.html" class="brand">ULMJC<span class="brand-sub">Grenoble</span></a>
    <button class="nav-toggle" aria-label="Menu" onclick="document.getElementById('nav-links').classList.toggle('open')">☰</button>
    <ul class="nav-links" id="nav-links">
      <?php foreach ($nav as $href => $n): ?>
      <li><a href="<?= e($href) ?>"<?= $page_active === $n[1] ? ' class="active"' : '' ?>><?= e($n[0]) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</header>
