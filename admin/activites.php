<?php
/* Back-office ULMJC : liste des Activités.
   Calqué sur la liste d'actualités d'admin/index.php (mêmes classes .alist/.arow…),
   adapté au type « activites » : tri par « ordre », vignette = image. */
require_once __DIR__ . '/auth.php';
require_login();

$activites = active_items('activites'); // hors corbeille
usort($activites, 'cmp_ordre');
$flash    = isset($_GET['ok']) ? $_GET['ok'] : '';
$savedSlug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']) : '';

admin_header('Activités');
?>
<?php if ($flash === 'saved'):
  $sv = $savedSlug ? find_activite($savedSlug) : null; ?>
  <div class="aflash">
    Activité enregistrée.
    <?php if ($sv && !empty($sv['published'])): ?>
      <a class="aflash-link" href="../activites.php" target="ulmjc_site">Voir la page Activités ↗</a>
    <?php elseif ($sv): ?>
      <span class="aflash-note">— brouillon, non visible sur le site</span>
    <?php endif; ?>
  </div>
<?php elseif ($flash === 'trashed'): ?><div class="aflash">Activité déplacée vers la corbeille. <a class="aflash-link" href="corbeille.php">Voir la corbeille ↗</a></div>
<?php elseif ($flash === 'deleted'): ?><div class="aflash">Activité supprimée.</div><?php endif; ?>

<div class="ahead">
  <div>
    <h1 class="atitle">Activités</h1>
    <p class="asub"><?= count($activites) ?> activité<?= count($activites) > 1 ? 's' : '' ?></p>
  </div>
  <div class="ahead-actions">
    <a class="abtn" href="activite-edit.php">+ Nouvelle activité</a>
  </div>
</div>

<?php if (!$activites): ?>
  <div class="acard aempty">Aucune activité pour le moment.<br/>Cliquez sur « Nouvelle activité » pour créer la première.</div>
<?php else: ?>
  <div class="alist">
    <?php foreach ($activites as $ac):
      $img = media_valid_src($ac['image'] ?? ''); ?>
      <div class="arow">
        <?php if ($img !== ''): ?>
          <span class="arow-cover" style="background-image:url('<?= e('../' . $img) ?>')"></span>
        <?php else: ?>
          <span class="arow-cover arow-cover--empty" aria-hidden="true">Sans photo</span>
        <?php endif; ?>
        <div class="arow-main">
          <div class="arow-title"><?= !empty($ac['title']) ? e($ac['title']) : '<span class="arow-untitled">Sans titre</span>' ?></div>
          <div class="arow-meta">
            <span class="abadge">Ordre <?= (int)($ac['ordre'] ?? 0) ?></span>
            <?php if (!empty($ac['jour'])): ?><span><?= e($ac['jour']) ?></span><?php endif; ?>
            <?php if (empty($ac['published'])): ?>
              <span class="abadge">Brouillon</span>
            <?php else: ?>
              <span class="abadge apub">En ligne</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="arow-actions">
          <a class="alink" href="activite-edit.php?slug=<?= e($ac['slug']) ?>">Modifier</a>
          <form method="post" action="activite-delete.php" onsubmit="return confirm('Mettre cette activité à la corbeille ?');">
            <?= csrf_field() ?>
            <input type="hidden" name="slug" value="<?= e($ac['slug']) ?>" />
            <button class="alink adanger" name="del" value="1">Supprimer</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php admin_footer(); ?>
