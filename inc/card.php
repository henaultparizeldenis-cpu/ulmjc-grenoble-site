<?php /* Carte d'actualité — attend $a. Style visuel ULMJC (classe .card + .actu-card).
   $card_prefix optionnel (ex. '../' en admin) pour préfixer les chemins d'image. */
$prefix = isset($card_prefix) ? $card_prefix : '';
$title  = display_title($a);
$href   = 'actu.php?slug=' . e($a['slug']);
$thumb  = list_thumb($a);
?>
<article class="card actu-card reveal">
  <?php if ($thumb !== ''): ?>
  <a href="<?= $href ?>" class="actu-card-cover" aria-label="<?= e($title) ?>">
    <img src="<?= e($prefix . $thumb) ?>" alt="" loading="lazy">
  </a>
  <?php endif; ?>
  <div class="actu-card-body">
    <div class="actu-card-meta"><?= fr_date($a['date'] ?? '') ?></div>
    <h3><a href="<?= $href ?>"><?= e($title) ?></a></h3>
    <?php if (!empty($a['excerpt'])): ?><p><?= e($a['excerpt']) ?></p><?php endif; ?>
    <a href="<?= $href ?>" class="card-link">Lire la suite</a>
  </div>
</article>
