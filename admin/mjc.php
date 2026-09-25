<?php
/* Back-office ULMJC : liste des maisons de l'union.
   Calquée sur admin/partenaires.php. Identifiant = « slug », tri par « ordre »,
   vignette = logo. Ces maisons alimentent à la fois la page Les MJC et la carte :
   en ajouter ou en retirer une se voit aux deux endroits. */
require_once __DIR__ . '/auth.php';
require_login();

$maisons = active_items('mjc'); // hors corbeille
usort($maisons, 'cmp_ordre');
$flash = isset($_GET['ok']) ? $_GET['ok'] : '';

/* Une maison sans coordonnées n'apparaîtra pas sur la carte : on le signale
   dans la liste plutôt que de laisser le point manquer en silence. */
$sansPoint = 0;
$sansBati = 0;
foreach ($maisons as $m) {
  if (empty($m['lat']) || empty($m['lon'])) $sansPoint++;
  /* Sans bâtiment désigné, la carte surligne celui qu'elle suppose, ce qui
     se trompe souvent : on le signale plutôt que de laisser croire. */
  elseif (empty($m['batiment'])) $sansBati++;
}

admin_header('Les MJC');
?>
<?php if ($flash === 'saved'): ?>
  <div class="aflash">Maison enregistrée. <a class="aflash-link" href="../les-mjc.php" target="ulmjc_site">Voir la page Les MJC ↗</a></div>
<?php elseif ($flash === 'trashed'): ?><div class="aflash">Maison déplacée vers la corbeille. <a class="aflash-link" href="corbeille.php">Voir la corbeille ↗</a></div>
<?php elseif ($flash === 'deleted'): ?><div class="aflash">Maison supprimée.</div><?php endif; ?>

<div class="ahead">
  <div>
    <h1 class="atitle">Les MJC</h1>
    <p class="asub"><?= count($maisons) ?> maison<?= count($maisons) > 1 ? 's' : '' ?><?php
      if ($sansPoint): ?> &middot; <?= $sansPoint ?> sans coordonnées, absente<?= $sansPoint > 1 ? 's' : '' ?> de la carte<?php
      endif;
      if ($sansBati): ?> &middot; <?= $sansBati ?> sans bâtiment désigné<?php
      endif; ?></p>
  </div>
  <div class="ahead-actions">
    <a class="alink" href="mjc-batiment.php">Désigner les bâtiments</a>
    <a class="abtn" href="mjc-edit.php">+ Nouvelle maison</a>
  </div>
</div>

<?php if (!$maisons): ?>
  <div class="acard aempty">Aucune maison pour le moment.<br/>Cliquez sur « Nouvelle maison » pour ajouter la première.</div>
<?php else: ?>
  <div class="alist">
    <?php foreach ($maisons as $m):
      $logo = mjc_logo_src($m['logo'] ?? ''); ?>
      <div class="arow">
        <?php if ($logo !== ''): ?>
          <span class="arow-cover" style="background-image:url('<?= e('../' . $logo) ?>');background-size:contain;background-color:#fff;"></span>
        <?php else: ?>
          <span class="arow-cover arow-cover--empty" aria-hidden="true">Sans logo</span>
        <?php endif; ?>
        <div class="arow-main">
          <div class="arow-title"><?= !empty($m['nom']) ? e($m['nom']) : '<span class="arow-untitled">Sans nom</span>' ?></div>
          <div class="arow-meta">
            <span class="abadge">Ordre <?= (int)($m['ordre'] ?? 0) ?></span>
            <?php if (!empty($m['quartier'])): ?><span><?= e($m['quartier']) ?></span><?php endif; ?>
            <?php if (empty($m['lat']) || empty($m['lon'])): ?>
              <span class="abadge">Hors carte</span>
            <?php elseif (empty($m['batiment'])): ?>
              <a class="abadge" href="mjc-batiment.php?slug=<?= e($m['slug']) ?>">Bâtiment supposé</a>
            <?php endif; ?>
            <?php if (empty($m['published'])): ?>
              <span class="abadge">Masquée</span>
            <?php else: ?>
              <span class="abadge apub">En ligne</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="arow-actions">
          <a class="alink" href="mjc-edit.php?slug=<?= e($m['slug']) ?>">Modifier</a>
          <form method="post" action="mjc-delete.php" onsubmit="return confirm('Mettre cette maison à la corbeille ?');">
            <?= csrf_field() ?>
            <input type="hidden" name="slug" value="<?= e($m['slug']) ?>" />
            <button class="alink adanger" name="del" value="1">Supprimer</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php admin_footer(); ?>
