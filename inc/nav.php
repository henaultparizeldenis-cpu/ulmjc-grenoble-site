<?php
/* Menu public ULMJC — partiel unique partagé par TOUTES les pages du site
   (pages statiques converties en PHP + pages CMS via inc/head.php).
   Markup identique à l'ancien <header class="site-header"> des pages statiques.

   État actif :
   - la page peut poser $active avant l'include (ex. $active='asso');
   - sinon, auto-détection d'après le nom du fichier courant.
   Cet include ne suppose PAS que lib.php soit chargé : il définit un
   échappeur local si besoin. */

if (!function_exists('e')) {
  function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// Clé de nav active : $active posé par la page, sinon auto-détection.
if (!isset($active)) {
  $__self = basename($_SERVER['PHP_SELF'] ?? '');
  $__map  = array(
    'index.php'            => 'accueil',
    'asso.php'             => 'asso',
    'les-mjc.php'          => 'mjc',
    'chalet.php'           => 'chalet',
    'activites.php'        => 'activites',
    'actus.php'            => 'actus',
    'actu.php'             => 'actus',
    'blog.php'             => 'blog',
    'billet.php'           => 'blog',
    'partenariats.php'     => 'partenaires',
    'contact.php'          => 'contact',
  );
  $active = $__map[$__self] ?? '';
}

// Items du menu, dans l'ordre : Accueil, Asso, MJC, Chalet, Activités,
// Actualités, Blog, Partenaires, Contact. Tous les liens en .php.
$__nav = array(
  array('index.php',        'Accueil',     'accueil'),
  array('asso.php',         'Asso',        'asso'),
  array('les-mjc.php',      'MJC',         'mjc'),
  array('chalet.php',       'Chalet',      'chalet'),
  array('activites.php',    'Activités',   'activites'),
  array('actus.php',        'Actualités',  'actus'),
  array('blog.php',         'Blog',        'blog'),
  array('partenariats.php', 'Partenaires', 'partenaires'),
  array('contact.php',      'Contact',     'contact'),
);
?>
<header class="site-header">
  <div class="container nav">
    <a href="index.php" class="brand">ULMJC<span class="brand-sub">Grenoble</span></a>
    <button class="nav-toggle" aria-label="Menu" onclick="document.getElementById('nav-links').classList.toggle('open')">☰</button>
    <ul class="nav-links" id="nav-links">
      <?php foreach ($__nav as $__item): ?>
      <li><a href="<?= e($__item[0]) ?>"<?= $active === $__item[2] ? ' class="active"' : '' ?>><?= e($__item[1]) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</header>
