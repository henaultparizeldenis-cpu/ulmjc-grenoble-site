<?php
/* Back-office ULMJC : liste des Partenaires.
   Calqué sur la liste d'actualités d'admin/index.php, adapté au type « partenaires » :
   identifiant = « id » (et non « slug »), tri par « ordre », vignette = logo. */
require_once __DIR__ . '/auth.php';
require_login();

$partenaires = active_items('partenaires'); // hors corbeille
usort($partenaires, 'cmp_ordre');
$flash = isset($_GET['ok']) ? $_GET['ok'] : '';

admin_header('Partenaires');
?>
<?php if ($flash === 'saved'): ?>
  <div class="aflash">Partenaire enregistré. <a class="aflash-link" href="../partenariats.php" target="ulmjc_site">Voir la page Partenaires ↗</a></div>
<?php elseif ($flash === 'trashed'): ?><div class="aflash">Partenaire déplacé vers la corbeille. <a class="aflash-link" href="corbeille.php">Voir la corbeille ↗</a></div>
<?php elseif ($flash === 'deleted'): ?><div class="aflash">Partenaire supprimé.</div><?php endif; ?>

<div class="ahead">
  <div>
    <h1 class="atitle">Partenaires</h1>
    <p class="asub"><?= count($partenaires) ?> partenaire<?= count($partenaires) > 1 ? 's' : '' ?></p>
  </div>
  <div class="ahead-actions">
    <a class="abtn" href="partenaire-edit.php">+ Nouveau partenaire</a>
  </div>
</div>

<?php if (!$partenaires): ?>
  <div class="acard aempty">Aucun partenaire pour le moment.<br/>Cliquez sur « Nouveau partenaire » pour ajouter le premier.</div>
<?php else: ?>
  <div class="alist">
    <?php foreach ($partenaires as $p):
      $logo = media_valid_src($p['logo'] ?? ''); ?>
      <div class="arow">
        <?php if ($logo !== ''): ?>
          <span class="arow-cover" style="background-image:url('<?= e('../' . $logo) ?>');background-size:contain;background-color:#fff;"></span>
        <?php else: ?>
          <span class="arow-cover arow-cover--empty" aria-hidden="true">Sans logo</span>
        <?php endif; ?>
        <div class="arow-main">
          <div class="arow-title"><?= !empty($p['nom']) ? e($p['nom']) : '<span class="arow-untitled">Sans nom</span>' ?></div>
          <div class="arow-meta">
            <span class="abadge">Ordre <?= (int)($p['ordre'] ?? 0) ?></span>
            <?php if (!empty($p['url'])): ?><span><?= e($p['url']) ?></span><?php endif; ?>
            <?php if (empty($p['published'])): ?>
              <span class="abadge">Masqué</span>
            <?php else: ?>
              <span class="abadge apub">En ligne</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="arow-actions">
          <a class="alink" href="partenaire-edit.php?id=<?= e($p['id']) ?>">Modifier</a>
          <form method="post" action="partenaire-delete.php" onsubmit="return confirm('Mettre ce partenaire à la corbeille ?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($p['id']) ?>" />
            <button class="alink adanger" name="del" value="1">Supprimer</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php admin_footer(); ?>
