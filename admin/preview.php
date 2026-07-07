<?php
/* Aperçu de mise en page ULMJC : rend l'élément (actu / activité / partenaire) NON
   ENCORE ENREGISTRÉ côté serveur, à partir des données postées par le formulaire
   d'édition. Affiché dans l'iframe du panneau d'aperçu live (_live_preview.php).

   Basé sur mohamed-cms/site/admin/preview.php. Adapté au CMS ULMJC :
   - plusieurs types (?type=actus|activites|partenaires) au lieu d'un seul ;
   - le rendu réutilise EXACTEMENT le markup des pages publiques de détail/carte
     (actu.php pour l'actu ; activites.php / partenariats.php pour un item) pour que
     l'aperçu soit fidèle ;
   - la feuille de style du site public (css/style.css) est chargée telle quelle ;
   - AUCUNE écriture disque : on construit un item temporaire en mémoire et on le rend.

   Sécurité : require_login ; sanitize_body() sur les corps HTML (comme au save) ;
   les chemins d'image restent limités à uploads/ ou images/ (media_valid_src). */
require_once __DIR__ . '/auth.php';
require_login();

$type = $_POST['type'] ?? ($_GET['type'] ?? '');
if (!in_array($type, array('actus', 'activites', 'partenaires'), true)) $type = 'actus';

/* ------------------------------------------------------------------
   Couverture / image / logo d'aperçu.
   Priorité : photo fraîchement choisie mais NON enregistrée (fichier uploadé →
   data URI), sinon le chemin choisi dans la médiathèque (uploads/ ou images/).
   Sous /admin/, les chemins médiathèque doivent être préfixés « ../ » pour
   s'afficher. Renvoie '' si retrait explicite ou rien.
   ------------------------------------------------------------------ */
function preview_cover_url() {
  if (!empty($_POST['cover_remove'])) return '';
  if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK
      && is_uploaded_file($_FILES['cover_file']['tmp_name'])
      && ($info = @getimagesize($_FILES['cover_file']['tmp_name']))) {
    return 'data:' . $info['mime'] . ';base64,' . base64_encode(file_get_contents($_FILES['cover_file']['tmp_name']));
  }
  $picked = media_valid_src($_POST['cover'] ?? '');
  return $picked !== '' ? '../' . $picked : '';
}

/* Corps HTML (actu / activité) : mêmes règles qu'au save (sanitize_body). Les
   images du corps arrivent en « uploads/… » / « images/… » (le formulaire retire
   le « ../ » avant d'envoyer) → on les repréfixe « ../ » pour l'affichage sous /admin/. */
function preview_body_html() {
  $body = sanitize_body($_POST['body'] ?? '');
  return str_replace(array('src="uploads/', 'src="images/'),
                     array('src="../uploads/', 'src="../images/'), $body);
}

/* Champs texte communs (jamais enregistrés ici). */
$coverUrl = preview_cover_url();

/* Filtre / effet / taille postés (validés comme au save). Le filtre s'applique
   ici DIRECTEMENT sur $coverUrl (qui peut être une data-URI d'un fichier non
   encore enregistré) → on reconstruit le style depuis cover_filters(), au lieu de
   passer par cover_style() qui attend un chemin disque. Résultat identique. */
$pv_filter = array_key_exists($_POST['filter'] ?? '', cover_filters()) ? (string)$_POST['filter'] : 'naturel';
$pv_effect = in_array($_POST['effect'] ?? '', array('kenburns','zoom','pano','fixe'), true) ? (string)$_POST['effect'] : 'kenburns';
$pv_cover_w = max(40, min(100, (int)($_POST['cover_w'] ?? 100)));
$pv_cover_align = !empty($_POST['cover_align']);

/* Style inline de couverture pour l'aperçu, à partir d'une URL quelconque (data: OK). */
function preview_cover_style($url, $filterKey) {
  if ($url === '') return '';
  $f = cover_filters()[array_key_exists($filterKey, cover_filters()) ? $filterKey : 'naturel'];
  $style = "background-image:" . $f['layers'] . "url('" . e($url) . "');background-size:cover;background-position:center;background-blend-mode:" . $f['blend'] . ";";
  if (!empty($f['css'])) $style .= "filter:" . $f['css'] . ";";
  return $style;
}
/* Classe d'effet pour l'aperçu (kenburns = animation par défaut, pas de classe). */
function preview_effect_class($effect) {
  $map = array('fixe' => ' fx-fixe', 'zoom' => ' fx-zoom', 'pano' => ' fx-pano');
  return isset($map[$effect]) ? $map[$effect] : '';
}

/* Détermine si des données ont été postées (1er chargement à vide → placeholder). */
$hasPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Aperçu — <?= e($type) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css?v=<?= e(ASSET_V) ?>" />
  <style>
    /* Dans l'aperçu, on NE charge PAS js/main.js : les éléments « .reveal » (masqués
       tant que le JS n'a pas ajouté « .in ») seraient invisibles. On les force visibles
       pour que l'aperçu montre le contenu immédiatement, sans changer le markup public. */
    .reveal, .reveal-stagger > * { opacity: 1 !important; transform: none !important; }
    body { background: var(--bg); }
    /* Cartouche discret d'aperçu (rappelle que rien n'est encore enregistré). */
    .lp-note{position:fixed;top:0;left:0;right:0;z-index:5;font-family:'Inter',sans-serif;
      font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#fff;
      background:var(--terra,#c4623a);text-align:center;padding:4px 8px;}
    body{padding-top:26px;}
    .lp-empty{max-width:520px;margin:22vh auto;text-align:center;font-family:'Inter',sans-serif;color:var(--ink-soft,#6b6660);}
    .lp-empty .lp-emoji{font-size:2.6rem;display:block;margin-bottom:.6rem;}
  </style>
</head>
<body>
<div class="lp-note">Aperçu en direct — modifications non enregistrées</div>
<?php
if (!$hasPost) {
  echo '<div class="lp-empty"><span class="lp-emoji">👁️</span>'
     . 'Commencez à remplir le formulaire : l\'aperçu de la page publique apparaîtra ici.'
     . '</div></body></html>';
  exit;
}

/* ==================================================================
   Rendu par type — reprend le markup EXACT des pages publiques.
   ================================================================== */
if ($type === 'actus') {
  /* Reprend actu.php (détail d'une actualité). */
  $title   = clean_utf8(trim($_POST['title'] ?? ''));
  if ($title === '') $title = '(Titre de l’actualité)';
  $date    = trim($_POST['date'] ?? '');
  $chapo   = clean_utf8(trim($_POST['chapo'] ?? ''));
  $body    = preview_body_html();
  ?>
  <style>
  /* Styles repris de actu.php (détail) — nécessaires hors de la page publique. */
  .actu-article-head{padding:3.5rem 0 0;}
  .actu-back{display:inline-block;font-size:.9rem;color:var(--terra-dark);margin-bottom:1.2rem;border:none;}
  .actu-article-meta{font-size:.85rem;color:var(--ink-soft);margin-top:.4rem;}
  .actu-hero{max-width:960px;margin:2.5rem auto 0;border-radius:var(--radius);overflow:hidden;aspect-ratio:16/9;background:var(--bg-soft);background-size:cover;background-position:center;}
  .actu-hero img{width:100%;height:100%;object-fit:cover;display:block;}
  .actu-content{max-width:720px;margin:0 auto;padding:2.5rem 0 1rem;}
  .actu-chapo{font-size:1.25rem;line-height:1.6;color:var(--pine);font-family:'Lora',Georgia,serif;font-style:italic;margin-bottom:1.8rem;}
  .actu-body h2{margin-top:2.4rem;}
  .actu-body h3{margin-top:1.8rem;}
  .actu-body ul{padding-left:1.4rem;}
  .actu-body li{margin-bottom:.5rem;}
  .actu-body img{max-width:100%;height:auto;border-radius:var(--radius-sm);margin:1.4rem 0;}
  .actu-body figure{margin:1.6rem 0;}
  .actu-body figure img{margin:0;}
  .actu-body figcaption{font-size:.85rem;color:var(--ink-soft);margin-top:.5rem;text-align:center;}
  .actu-body blockquote{border-left:4px solid var(--terra);background:var(--bg-soft);margin:1.6rem 0;padding:1rem 1.4rem;border-radius:var(--radius-sm);font-family:'Lora',Georgia,serif;font-size:1.1rem;color:var(--pine);}
  .actu-body .al-center{text-align:center;}
  .actu-body .al-right{text-align:right;}
  </style>

  <div class="page-header actu-article-head">
    <div class="container">
      <a href="#" class="actu-back" onclick="return false;">← Retour aux actualités</a>
      <span class="section-eyebrow">Actualité</span>
      <h1><?= e($title) ?></h1>
      <div class="actu-article-meta"><?= $date ? fr_date($date) : '' ?> · <?= reading_time($body) ?></div>
    </div>
  </div>

  <section>
    <div class="container">
      <?php if ($coverUrl !== ''):
        $heroMax = $pv_cover_align ? 720 : (int)round(960 * $pv_cover_w / 100);
      ?>
      <div class="actu-hero reveal<?= preview_effect_class($pv_effect) ?>" role="img" aria-label="<?= e($title) ?>" style="<?= preview_cover_style($coverUrl, $pv_filter) ?>max-width:<?= $heroMax ?>px;"></div>
      <?php endif; ?>

      <div class="actu-content">
        <?php if ($chapo !== ''): ?>
          <p class="actu-chapo reveal"><?= e($chapo) ?></p>
        <?php endif; ?>
        <div class="actu-body reveal"><?= $body ?></div>
      </div>
    </div>
  </section>
  <?php
} elseif ($type === 'activites') {
  /* Reprend activites.php (UNE fiche d'activité). Le champ image utilise le même
     moule que la couverture d'actu (cover / cover_file / cover_remove) ; le save
     le stocke ensuite sous « image ». */
  $title   = clean_utf8(trim($_POST['title'] ?? ''));
  if ($title === '') $title = '(Titre de l’activité)';
  $jour    = clean_utf8(trim($_POST['jour'] ?? ''));
  $horaire = clean_utf8(trim($_POST['horaire'] ?? ''));
  $public  = clean_utf8(trim($_POST['public'] ?? ''));
  $desc    = preview_body_html();
  ?>
  <style>
  /* Styles repris de activites.php (fiche). */
  .act-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.6rem;margin-top:2.5rem;}
  .act-card{background:var(--bg-card);border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;display:flex;flex-direction:column;transition:transform .2s,box-shadow .2s,border-color .2s;}
  .act-card:hover{transform:translateY(-3px);box-shadow:var(--shadow);border-color:var(--pine-soft);}
  .act-card-cover{display:block;aspect-ratio:16/10;overflow:hidden;background:var(--bg-soft);background-size:cover;background-position:center;}
  .act-card-cover img{width:100%;height:100%;object-fit:cover;display:block;}
  .act-card-body{padding:1.6rem;display:flex;flex-direction:column;flex:1;}
  .act-card-body h3{color:var(--pine);margin-bottom:.5rem;}
  .act-tags{display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.9rem;}
  .act-tag{display:inline-block;font-size:.74rem;font-weight:500;letter-spacing:.04em;color:var(--terra-dark);background:var(--bg-soft);border:1px solid var(--taupe);border-radius:12px;padding:.15rem .6rem;}
  .act-desc{color:var(--ink-soft);font-size:.95rem;}
  .act-desc p{margin:0 0 .6rem;}
  .act-desc p:last-child{margin-bottom:0;}
  </style>

  <div class="page-header">
    <div class="container">
      <span class="section-eyebrow">Activités</span>
      <h1>Tout ce qu'on peut faire ici.</h1>
      <p class="lede">Aperçu de la fiche telle qu'elle apparaîtra dans la grille des activités.</p>
    </div>
  </div>

  <section>
    <div class="container">
      <div class="act-grid reveal-stagger">
        <article class="act-card reveal">
          <?php if ($coverUrl !== ''): ?>
          <div class="act-card-cover<?= preview_effect_class($pv_effect) ?>" role="img" aria-label="<?= e($title) ?>" style="<?= preview_cover_style($coverUrl, $pv_filter) ?>"></div>
          <?php endif; ?>
          <div class="act-card-body">
            <h3><?= e($title) ?></h3>
            <?php if ($jour !== '' || $horaire !== '' || $public !== ''): ?>
            <div class="act-tags">
              <?php if ($jour !== ''): ?><span class="act-tag">📅 <?= e($jour) ?></span><?php endif; ?>
              <?php if ($horaire !== ''): ?><span class="act-tag">🕒 <?= e($horaire) ?></span><?php endif; ?>
              <?php if ($public !== ''): ?><span class="act-tag">👥 <?= e($public) ?></span><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="act-desc"><?= $desc ?></div>
          </div>
        </article>
      </div>
    </div>
  </section>
  <?php
} else { /* partenaires */
  /* Reprend partenariats.php (UN partenaire). Le logo utilise le moule « cover ». */
  $nom = clean_utf8(trim($_POST['nom'] ?? ''));
  if ($nom === '') $nom = '(Nom du partenaire)';
  $url = trim($_POST['url'] ?? '');
  if ($url !== '' && !preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
  $ext = ($url !== '' && preg_match('#^https?://[^\s"\'<>]+$#i', $url));
  $tag = $ext ? 'a' : 'div';
  ?>
  <style>
  /* Styles repris de partenariats.php. */
  .part-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1.4rem;margin-top:2.5rem;}
  .part-item{display:flex;align-items:center;justify-content:center;background:var(--bg-card);border:1px solid var(--line);border-radius:var(--radius);padding:1.6rem;min-height:140px;transition:transform .2s,box-shadow .2s,border-color .2s;}
  .part-item:hover{transform:translateY(-3px);box-shadow:var(--shadow);border-color:var(--pine-soft);}
  .part-item img{max-width:100%;max-height:96px;width:auto;height:auto;object-fit:contain;display:block;}
  .part-item .part-name{font-family:'Lora',Georgia,serif;color:var(--pine);text-align:center;font-size:1.1rem;line-height:1.3;}
  a.part-item{border:1px solid var(--line);}
  a.part-item:hover{border-color:var(--pine-soft);}
  </style>

  <div class="page-header">
    <div class="container">
      <span class="section-eyebrow">Notre écosystème</span>
      <h1>Partenariats.</h1>
      <p class="lede">Aperçu de la vignette telle qu'elle apparaîtra dans la grille des partenaires.</p>
    </div>
  </div>

  <section>
    <div class="container">
      <div class="part-grid reveal-stagger">
        <<?= $tag ?> class="part-item"<?= $ext ? ' href="' . e($url) . '" target="_blank" rel="noopener" onclick="return false;"' : '' ?> title="<?= e($nom) ?>">
          <?php if ($coverUrl !== ''): ?>
            <img src="<?= e($coverUrl) ?>" alt="<?= e($nom) ?>">
          <?php else: ?>
            <span class="part-name"><?= e($nom) ?></span>
          <?php endif; ?>
        </<?= $tag ?>>
      </div>
    </div>
  </section>
  <?php
}
?>
</body>
</html>
