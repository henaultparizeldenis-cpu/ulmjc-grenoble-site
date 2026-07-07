<?php
/* Corbeille du back-office ULMJC : éléments supprimés (soft-delete) des 4 types
   « liste » (Actualités / Blog / Activités / Partenaires), regroupés par type.
   Pour chacun : vignette + titre + date de suppression, et deux actions —
   RESTAURER (revient dans la liste) et SUPPRIMER DÉFINITIVEMENT (irréversible).
   Les actions passent par corbeille-action.php (POST + CSRF). Calqué sur les
   listes admin (mêmes classes .alist/.arow…). */
require_once __DIR__ . '/auth.php';
require_login();

/* Métadonnées d'affichage par type. 'key' = champ identifiant (slug/id) ;
   'title' = champ titre ; 'thumb' = champ image ; 'contain' = logo (fond blanc). */
$TYPES = array(
  'actus'       => array('label' => 'Actualités',  'key' => 'slug', 'title' => 'title', 'thumb' => 'cover', 'contain' => false),
  'blog'        => array('label' => 'Blog',         'key' => 'slug', 'title' => 'title', 'thumb' => 'cover', 'contain' => false),
  'activites'   => array('label' => 'Activités',    'key' => 'slug', 'title' => 'title', 'thumb' => 'image', 'contain' => false),
  'partenaires' => array('label' => 'Partenaires',  'key' => 'id',   'title' => 'nom',   'thumb' => 'logo',  'contain' => true),
);

$total = trashed_count();

admin_header('Corbeille');
?>
<div class="ahead">
  <div>
    <h1 class="atitle">Corbeille</h1>
    <p class="asub">
      <?php if ($total > 0): ?>
        <?= $total ?> élément<?= $total > 1 ? 's' : '' ?> supprimé<?= $total > 1 ? 's' : '' ?> — récupérable<?= $total > 1 ? 's' : '' ?> tant que vous ne le<?= $total > 1 ? 's' : '' ?> supprimez pas définitivement.
      <?php else: ?>
        La corbeille est vide.
      <?php endif; ?>
    </p>
  </div>
  <?php if ($total > 0): ?>
  <div class="ahead-actions">
    <form method="post" action="corbeille-action.php" onsubmit="return confirm('Vider la corbeille : cette action est définitive et irréversible. Supprimer pour de bon TOUS les éléments de la corbeille ?');">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="empty" />
      <button class="abtn abtn-ghost adanger" name="do" value="1">Vider la corbeille</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<?php if ($total === 0): ?>
  <div class="acard aempty">La corbeille est vide.</div>
<?php else: ?>
  <?php foreach ($TYPES as $type => $meta):
    $items = trashed_items($type);
    if (!$items) continue; ?>
    <h2 class="atitle" style="font-size:1.15rem;margin:1.6rem 0 .6rem;"><?= e($meta['label']) ?> <span class="asub" style="font-weight:400;">(<?= count($items) ?>)</span></h2>
    <div class="alist">
      <?php foreach ($items as $it):
        $key   = (string)($it[$meta['key']] ?? '');
        $title = trim((string)($it[$meta['title']] ?? ''));
        if ($title === '') $title = 'Sans titre';
        $thumb = media_valid_src($it[$meta['thumb']] ?? '');
        $delAt = !empty($it['deleted_at']) ? date('d/m/Y à H\hi', strtotime($it['deleted_at'])) : '';
      ?>
        <div class="arow">
          <?php if ($thumb !== ''): ?>
            <span class="arow-cover" style="background-image:url('<?= e('../' . $thumb) ?>');<?= $meta['contain'] ? 'background-size:contain;background-color:#fff;' : '' ?>"></span>
          <?php else: ?>
            <span class="arow-cover arow-cover--empty" aria-hidden="true">Sans image</span>
          <?php endif; ?>
          <div class="arow-main">
            <div class="arow-title"><?= e($title) ?></div>
            <div class="arow-meta">
              <?php if ($delAt !== ''): ?>Supprimé le <?= e($delAt) ?><?php else: ?>Supprimé<?php endif; ?>
            </div>
          </div>
          <div class="arow-actions">
            <form method="post" action="corbeille-action.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="restore" />
              <input type="hidden" name="type" value="<?= e($type) ?>" />
              <input type="hidden" name="key" value="<?= e($key) ?>" />
              <button class="alink" name="do" value="1">Restaurer</button>
            </form>
            <form method="post" action="corbeille-action.php" onsubmit="return confirm('Cette action est définitive et irréversible. Supprimer pour de bon « <?= e(str_replace(array('\\', "'"), array('\\\\', "\\'"), $title)) ?> » ?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="purge" />
              <input type="hidden" name="type" value="<?= e($type) ?>" />
              <input type="hidden" name="key" value="<?= e($key) ?>" />
              <button class="alink adanger" name="do" value="1">Supprimer définitivement</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
<?php admin_footer(); ?>
