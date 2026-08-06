<?php
/* Back-office ULMJC : liste des offres d'emploi. Calqué sur admin/blog.php.
   Différences : pas de vignette (une offre n'a pas d'image), méta = contrat /
   temps / lieu, et une pastille « Date limite dépassée » quand la date limite
   de candidature est passée (l'offre reste en base mais n'est plus visible
   sur le site public — voir published_emplois() dans inc/lib.php). */
require_once __DIR__ . '/auth.php';
require_login();

/* --- Liste des offres (hors corbeille), plus récentes d'abord --- */
$offres = active_items('emplois');
usort($offres, function ($x, $y) {
  return strcmp(($y['date'] ?? '') . ($y['created'] ?? ''), ($x['date'] ?? '') . ($x['created'] ?? ''));
});
$flash = isset($_GET['ok']) ? $_GET['ok'] : '';
$savedSlug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']) : '';

admin_header("Offres d'emploi");
?>
<?php if ($flash === 'saved'):
  $sv = $savedSlug ? find_emploi($savedSlug) : null; ?>
  <div class="aflash">
    Offre enregistrée.
    <?php if ($sv && !empty($sv['published']) && !emploi_expired($sv)): ?>
      <a class="aflash-link" href="../offre.php?slug=<?= e($sv['slug']) ?>" target="ulmjc_site">Voir l'offre ↗</a>
    <?php elseif ($sv && !empty($sv['published']) && emploi_expired($sv)): ?>
      <span class="aflash-note">— date limite dépassée, l'offre n'est plus affichée sur le site</span>
    <?php elseif ($sv): ?>
      <span class="aflash-note">— brouillon, non visible sur le site</span>
    <?php endif; ?>
  </div>
<?php elseif ($flash === 'trashed'): ?><div class="aflash">Offre déplacée vers la corbeille. <a class="aflash-link" href="corbeille.php">Voir la corbeille ↗</a></div>
<?php elseif ($flash === 'deleted'): ?><div class="aflash">Offre supprimée.</div><?php endif; ?>

<div class="ahead">
  <div>
    <h1 class="atitle">Offres d'emploi</h1>
    <p class="asub"><?= count($offres) ?> offre<?= count($offres) > 1 ? 's' : '' ?></p>
  </div>
  <div class="ahead-actions">
    <a class="abtn" href="offre-edit.php">+ Nouvelle offre</a>
  </div>
</div>

<?php if (!$offres): ?>
  <div class="acard aempty">Aucune offre pour le moment.<br/>Cliquez sur « Nouvelle offre » pour publier la première.</div>
<?php else: ?>
  <div class="alist">
    <?php foreach ($offres as $a):
      $contrat = emploi_contrat_label(emploi_contrat_key($a));
      $temps   = trim((string)($a['temps'] ?? ''));
      $lieu    = trim((string)($a['lieu'] ?? ''));
      $limite  = emploi_deadline($a);
      $expired = emploi_expired($a);
    ?>
      <div class="arow">
        <div class="arow-main">
          <div class="arow-title"><?= !empty($a['title']) ? e($a['title']) : '<span class="arow-untitled">' . e(display_title($a)) . '</span>' ?></div>
          <div class="arow-meta">
            <?php if ($contrat !== ''): ?><?= e($contrat) ?> · <?php endif; ?>
            <?php if ($temps !== ''): ?><?= e($temps) ?> · <?php endif; ?>
            <?php if ($lieu !== ''): ?><?= e($lieu) ?> · <?php endif; ?>
            <?= fr_date($a['date'] ?? '') ?>
            <?php if (empty($a['published'])): ?>
              <span class="abadge">Brouillon</span>
            <?php else: ?>
              <span class="abadge apub">En ligne</span>
            <?php endif; ?>
            <?php if ($expired): ?>
              <span class="abadge aexpired" title="L'offre n'est plus affichée sur le site public">Date limite dépassée</span>
            <?php elseif ($limite !== ''): ?>
              <span class="abadge">Jusqu'au <?= e(fr_date($limite)) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="arow-actions">
          <a class="alink" href="offre-edit.php?slug=<?= e($a['slug']) ?>">Modifier</a>
          <?php if (!empty($a['published']) && !$expired): ?>
            <a class="alink" href="../offre.php?slug=<?= e($a['slug']) ?>" target="ulmjc_site">Voir</a>
          <?php endif; ?>
          <form method="post" action="offre-delete.php" onsubmit="return confirm('Mettre cette offre à la corbeille ?');">
            <?= csrf_field() ?>
            <input type="hidden" name="slug" value="<?= e($a['slug']) ?>" />
            <button class="alink adanger" name="del" value="1">Supprimer</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<style>
/* Pastille « date limite dépassée » : même moule que .abadge (admin.css),
   en teinte terre cuite pour signaler que l'offre est retirée du site. */
.abadge.aexpired{background:#fbeee8;border-color:var(--terra,#c4623a);color:var(--terra-dark,#a04e2c);}
</style>
<?php admin_footer(); ?>
