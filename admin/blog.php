<?php
/* Back-office ULMJC : liste des billets de blog. Basé sur admin/index.php
   (uniquement la partie « tableau de bord », l'auth/reset restant dans index.php).
   Ajoute par rapport aux actus : colonne catégorie + auteur. */
require_once __DIR__ . '/auth.php';
require_login();

/* --- Liste des billets --- */
$billets = load_blog();
usort($billets, function ($x, $y) {
  return strcmp(($y['date'] ?? '') . ($y['created'] ?? ''), ($x['date'] ?? '') . ($x['created'] ?? ''));
});
$flash = isset($_GET['ok']) ? $_GET['ok'] : '';
$savedSlug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']) : '';

admin_header('Blog');
?>
<?php if ($flash === 'saved'):
  $sv = $savedSlug ? find_blog($savedSlug) : null; ?>
  <div class="aflash">
    Billet enregistré.
    <?php if ($sv && !empty($sv['published'])): ?>
      <a class="aflash-link" href="../billet.php?slug=<?= e($sv['slug']) ?>" target="ulmjc_site">Voir le billet ↗</a>
    <?php elseif ($sv): ?>
      <span class="aflash-note">— brouillon, non visible sur le site</span>
    <?php endif; ?>
  </div>
<?php elseif ($flash === 'deleted'): ?><div class="aflash">Billet supprimé.</div><?php endif; ?>

<div class="ahead">
  <div>
    <h1 class="atitle">Blog</h1>
    <p class="asub"><?= count($billets) ?> billet<?= count($billets) > 1 ? 's' : '' ?></p>
  </div>
  <div class="ahead-actions">
    <a class="abtn" href="billet-edit.php">+ Nouveau billet</a>
  </div>
</div>

<?php if (!$billets): ?>
  <div class="acard aempty">Aucun billet pour le moment.<br/>Cliquez sur « Nouveau billet » pour publier le premier.</div>
<?php else: ?>
  <div class="alist">
    <?php foreach ($billets as $a):
      $catLabel = blog_category_label(blog_category_key($a));
      $author   = blog_author($a);
    ?>
      <div class="arow">
        <?php if (has_thumb($a)): ?>
          <span class="arow-cover" style="background-image:url('<?= e('../' . list_thumb($a)) ?>')"></span>
        <?php else: ?>
          <span class="arow-cover arow-cover--empty" aria-hidden="true">Sans photo</span>
        <?php endif; ?>
        <div class="arow-main">
          <div class="arow-title"><?= !empty($a['title']) ? e($a['title']) : '<span class="arow-untitled">' . e(display_title($a)) . '</span>' ?></div>
          <div class="arow-meta">
            <?php if ($catLabel !== ''): ?><?= e($catLabel) ?> · <?php endif; ?>
            <?php if ($author !== ''): ?>par <?= e($author) ?> · <?php endif; ?>
            <?= fr_date($a['date'] ?? '') ?>
            <?php if (empty($a['published'])): ?>
              <span class="abadge">Brouillon</span>
            <?php else: ?>
              <span class="abadge apub">En ligne</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="arow-actions">
          <a class="alink" href="billet-edit.php?slug=<?= e($a['slug']) ?>">Modifier</a>
          <?php if (!empty($a['published'])): ?>
            <a class="alink" href="../billet.php?slug=<?= e($a['slug']) ?>" target="ulmjc_site">Voir</a>
          <?php endif; ?>
          <form method="post" action="billet-delete.php" onsubmit="return confirm('Supprimer définitivement ce billet ?');">
            <?= csrf_field() ?>
            <input type="hidden" name="slug" value="<?= e($a['slug']) ?>" />
            <button class="alink adanger" name="del" value="1">Supprimer</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php admin_footer(); ?>
