<?php
/* Enregistrement d'une actualité. Basé sur mohamed-cms/site/admin/save.php.
   Adapté : type unique (actus), couverture depuis un upload (cover_file) OU la
   médiathèque (champ caché « cover »). Spécifique avocat (filtres/effets/galeries/
   largeur de couverture) retiré. CSRF + clean_utf8 + sanitize_body conservés. */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) {
  header('Location: index.php'); exit;
}

$title     = clean_utf8(trim($_POST['title'] ?? ''));
$date      = trim($_POST['date'] ?? '');
$excerpt   = clean_utf8(trim($_POST['excerpt'] ?? ''));
$chapo     = clean_utf8(trim($_POST['chapo'] ?? ''));
$body      = sanitize_body($_POST['body'] ?? '');
$published = !empty($_POST['published']);
$origSlug  = preg_replace('/[^a-z0-9\-]/', '', $_POST['orig_slug'] ?? '');
// Couverture choisie dans la médiathèque (chemin uploads/ ou images/), validée.
$pickedCover = media_valid_src($_POST['cover'] ?? '');

// Filtre / effet / taille de la couverture (validés contre les listes autorisées).
$filter     = array_key_exists($_POST['filter'] ?? '', cover_filters()) ? (string)$_POST['filter'] : 'naturel';
$effect     = in_array($_POST['effect'] ?? '', array('kenburns','zoom','pano','fixe'), true) ? (string)$_POST['effect'] : 'kenburns';
$coverW     = max(40, min(100, (int)($_POST['cover_w'] ?? 100)));
$coverAlign = !empty($_POST['cover_align']);

// Le titre est obligatoire.
if ($title === '') { header('Location: edit.php' . ($origSlug ? '?slug=' . $origSlug : '')); exit; }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

$actus = load_actus();

// Ancienne couverture (pour nettoyage si remplacée/retirée)
$prevCover = '';
if ($origSlug) { $prev = find_actu($origSlug); if ($prev) $prevCover = $prev['cover'] ?? ''; }

/* Slug : stable en édition, généré à la création. */
if ($origSlug && find_actu($origSlug)) {
  $slug = $origSlug;
} else {
  $base = slugify($title);
  $slug = unique_slug('actus', $base);
}

/* Couverture. Priorité : (1) nouveau fichier uploadé ; (2) sinon retrait explicite ;
   (3) sinon image choisie dans la médiathèque ; (4) sinon on garde l'ancienne. */
$cover = $prevCover;
if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK && is_uploaded_file($_FILES['cover_file']['tmp_name'])) {
  $tmp = $_FILES['cover_file']['tmp_name'];
  if (@getimagesize($tmp)) {
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
    $fname = $slug . '-' . time() . '.jpg';
    $dest  = UPLOAD_DIR . '/' . $fname;
    if (optimize_image($tmp, $dest)) {
      // supprime l'ancienne couverture UPLOADÉE si remplacée (jamais une image fournie du dépôt)
      $old = upload_path($prevCover);
      if ($old !== '' && is_file($old)) @unlink($old);
      $cover = UPLOAD_URL . '/' . $fname;
    }
  }
} elseif (!empty($_POST['cover_remove'])) {
  $old = upload_path($prevCover);
  if ($old !== '' && is_file($old)) @unlink($old);
  $cover = '';
} elseif ($pickedCover !== '' && $pickedCover !== $prevCover) {
  // Nouvelle image choisie dans la médiathèque : on remplace (nettoie l'ancien upload).
  $old = upload_path($prevCover);
  if ($old !== '' && is_file($old)) @unlink($old);
  $cover = $pickedCover;
}

/* Conserve la date de création si l'actu existe déjà. */
$created = date('c');
foreach ($actus as $it) {
  if (($it['slug'] ?? '') === $slug && !empty($it['created'])) { $created = $it['created']; break; }
}

$record = array(
  'slug'      => $slug,
  'title'     => $title,
  'date'      => $date,
  'excerpt'   => $excerpt,
  'chapo'     => $chapo,
  'cover'     => $cover,
  'filter'    => $filter,
  'effect'    => $effect,
  'cover_w'   => $coverW,
  'cover_align' => $coverAlign,
  'body'      => $body,
  'published' => $published,
  'created'   => $created,
  'updated'   => date('c'),
);

/* Remplace ou ajoute. */
$found = false;
foreach ($actus as $i => $it) {
  if (($it['slug'] ?? '') === $slug) { $actus[$i] = $record; $found = true; break; }
}
if (!$found) $actus[] = $record;

save_actus($actus);
header('Location: index.php?ok=saved&slug=' . urlencode($slug));
exit;
