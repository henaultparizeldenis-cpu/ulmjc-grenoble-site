<?php /* Carte d'offre d'emploi — attend $a. Variante de inc/card-blog.php SANS photo
   (une offre n'a pas d'image) : le contrat, le temps de travail et le lieu sont
   affichés en étiquettes. Style visuel ULMJC (.card + .actu-card + .emploi-*).
   Les règles .emploi-* sont déclarées par les pages qui incluent cette carte
   (emplois.php, offre.php), comme le fait déjà inc/card-blog.php. */
$title   = display_title($a);
$href    = 'offre.php?slug=' . e($a['slug']);
$contrat = emploi_contrat_label(emploi_contrat_key($a));
$temps   = trim((string)($a['temps'] ?? ''));
$lieu    = trim((string)($a['lieu'] ?? ''));
$limite  = emploi_deadline($a);
?>
<article class="card actu-card emploi-card reveal">
  <div class="actu-card-body">
    <div class="actu-card-meta">
      <?php if ($contrat !== ''): ?><?= e($contrat) ?> · <?php endif; ?>
      <?= fr_date($a['date'] ?? '') ?>
    </div>
    <h3><a href="<?= $href ?>"><?= e($title) ?></a></h3>
    <?php if ($temps !== '' || $lieu !== ''): ?>
    <div class="emploi-tags">
      <?php if ($temps !== ''): ?><span class="emploi-tag">🕒 <?= e($temps) ?></span><?php endif; ?>
      <?php if ($lieu !== ''): ?><span class="emploi-tag">📍 <?= e($lieu) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($a['excerpt'])): ?><p><?= e($a['excerpt']) ?></p><?php endif; ?>
    <?php if ($limite !== ''): ?><div class="emploi-limit">Candidatures jusqu'au <?= fr_date($limite) ?></div><?php endif; ?>
    <a href="<?= $href ?>" class="card-link">Voir l'offre</a>
  </div>
</article>
