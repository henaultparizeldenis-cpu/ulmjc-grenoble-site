<?php
/* Enregistrement d'un billet de blog. Basé sur admin/save.php (actus).
   Identique aux actus (couverture upload OU médiathèque, filtre/effet/taille,
   CSRF, clean_utf8, sanitize_body) + deux champs propres au blog :
   AUTEUR (nettoyé) et CATÉGORIE (validée contre blog_categories()). */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) {
  header('Location: blog.php'); exit;
}

$title     = clean_utf8(trim($_POST['title'] ?? ''));
$date      = trim($_POST['date'] ?? '');
$author    = clean_utf8(trim($_POST['author'] ?? ''));
$excerpt   = clean_utf8(trim($_POST['excerpt'] ?? ''));
$chapo     = clean_utf8(trim($_POST['chapo'] ?? ''));
$body      = sanitize_body($_POST['body'] ?? '');
$published = !empty($_POST['published']);
$origSlug  = preg_replace('/[^a-z0-9\-]/', '', $_POST['orig_slug'] ?? '');
// Couverture choisie dans la médiathèque (chemin uploads/ ou images/), validée.
$pickedCover = media_valid_src($_POST['cover'] ?? '');

// Catégorie validée contre la liste des thèmes du blog ('' si inconnue/absente).
$category = array_key_exists($_POST['category'] ?? '', blog_categories()) ? (string)$_POST['category'] : '';

// Filtre / effet / taille de la couverture (validés contre les listes autorisées).
$filter     = array_key_exists($_POST['filter'] ?? '', cover_filters()) ? (string)$_POST['filter'] : 'naturel';
$effect     = in_array($_POST['effect'] ?? '', array('kenburns','zoom','pano','fixe'), true) ? (string)$_POST['effect'] : 'kenburns';
$coverW     = max(40, min(100, (int)($_POST['cover_w'] ?? 100)));
$coverAlign = !empty($_POST['cover_align']);

// Le titre est obligatoire.
if ($title === '') { header('Location: billet-edit.php' . ($origSlug ? '?slug=' . $origSlug : '')); exit; }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

$billets = load_blog();

// Ancienne couverture (pour nettoyage si remplacée/retirée)
$prevCover = '';
if ($origSlug) { $prev = find_blog($origSlug); if ($prev) $prevCover = $prev['cover'] ?? ''; }

/* Slug : stable en édition, généré à la création. */
if ($origSlug && find_blog($origSlug)) {
  $slug = $origSlug;
} else {
  $base = slugify($title);
  $slug = unique_slug('blog', $base);
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

/* Conserve la date de création si le billet existe déjà. */
$created = date('c');
foreach ($billets as $it) {
  if (($it['slug'] ?? '') === $slug && !empty($it['created'])) { $created = $it['created']; break; }
}

$record = array(
  'slug'      => $slug,
  'title'     => $title,
  'date'      => $date,
  'author'    => $author,
  'category'  => $category,
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
foreach ($billets as $i => $it) {
  if (($it['slug'] ?? '') === $slug) { $billets[$i] = $record; $found = true; break; }
}
if (!$found) $billets[] = $record;

save_blog($billets);
header('Location: blog.php?ok=saved&slug=' . urlencode($slug));
exit;
