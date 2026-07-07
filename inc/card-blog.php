<?php /* Carte de billet de blog — attend $a. Variante de inc/card.php qui montre
   en plus la CATÉGORIE (thème) et l'AUTEUR. Style visuel ULMJC (.card + .actu-card).
   L'attribut data-cat porte la clé de catégorie pour le filtre client de blog.php.
   $card_prefix optionnel (ex. '../' en admin) pour préfixer les chemins d'image. */
$prefix   = isset($card_prefix) ? $card_prefix : '';
$title    = display_title($a);
$href     = 'billet.php?slug=' . e($a['slug']);
$thumb    = list_thumb($a);
$catKey   = blog_category_key($a);
$catLabel = blog_category_label($catKey);
$author   = blog_author($a);
?>
<article class="card actu-card blog-card reveal" data-cat="<?= e($catKey) ?>">
  <?php if ($thumb !== ''): ?>
  <a href="<?= $href ?>" class="actu-card-cover" aria-label="<?= e($title) ?>">
    <img src="<?= e($prefix . $thumb) ?>" alt="" loading="lazy">
  </a>
  <?php endif; ?>
  <div class="actu-card-body">
    <div class="actu-card-meta">
      <?php if ($catLabel !== ''): ?><span class="blog-card-cat"><?= e($catLabel) ?></span> · <?php endif; ?>
      <?= fr_date($a['date'] ?? '') ?>
    </div>
    <h3><a href="<?= $href ?>"><?= e($title) ?></a></h3>
    <?php if (!empty($a['excerpt'])): ?><p><?= e($a['excerpt']) ?></p><?php endif; ?>
    <?php if ($author !== ''): ?><div class="blog-card-author">Par <?= e($author) ?></div><?php endif; ?>
    <a href="<?= $href ?>" class="card-link">Lire le billet</a>
  </div>
</article>
