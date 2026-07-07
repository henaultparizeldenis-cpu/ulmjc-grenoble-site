<?php
/* Enregistrement d'une activité. Calqué sur admin/save.php (actualités).
   Type « activites » : champs title, jour, horaire, public, ordre, description (HTML
   nettoyé), image (upload cover_file OU médiathèque champ « cover »). CSRF + clean_utf8
   + sanitize_body conservés. */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) {
  header('Location: activites.php'); exit;
}

$title       = clean_utf8(trim($_POST['title'] ?? ''));
$jour        = clean_utf8(trim($_POST['jour'] ?? ''));
$horaire     = clean_utf8(trim($_POST['horaire'] ?? ''));
$public      = clean_utf8(trim($_POST['public'] ?? ''));
$ordre       = (int)($_POST['ordre'] ?? 0);
$description = sanitize_body($_POST['body'] ?? '');
$published   = !empty($_POST['published']);
$origSlug    = preg_replace('/[^a-z0-9\-]/', '', $_POST['orig_slug'] ?? '');
// Image choisie dans la médiathèque (chemin uploads/ ou images/), validée.
$pickedCover = media_valid_src($_POST['cover'] ?? '');

// Le titre est obligatoire.
if ($title === '') { header('Location: activite-edit.php' . ($origSlug ? '?slug=' . $origSlug : '')); exit; }

$activites = load_activites();

// Ancienne image (pour nettoyage si remplacée/retirée)
$prevCover = '';
if ($origSlug) { $prev = find_activite($origSlug); if ($prev) $prevCover = $prev['image'] ?? ''; }

/* Slug : stable en édition, généré à la création (unicité par type). */
if ($origSlug && find_activite($origSlug)) {
  $slug = $origSlug;
} else {
  $slug = unique_slug('activites', slugify($title));
}

/* Image. Priorité : (1) nouveau fichier uploadé ; (2) retrait explicite ;
   (3) image choisie dans la médiathèque ; (4) sinon on garde l'ancienne. */
$image = $prevCover;
if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK && is_uploaded_file($_FILES['cover_file']['tmp_name'])) {
  $tmp = $_FILES['cover_file']['tmp_name'];
  if (@getimagesize($tmp)) {
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
    $fname = $slug . '-' . time() . '.jpg';
    $dest  = UPLOAD_DIR . '/' . $fname;
    if (optimize_image($tmp, $dest)) {
      $old = upload_path($prevCover);
      if ($old !== '' && is_file($old)) @unlink($old);
      $image = UPLOAD_URL . '/' . $fname;
    }
  }
} elseif (!empty($_POST['cover_remove'])) {
  $old = upload_path($prevCover);
  if ($old !== '' && is_file($old)) @unlink($old);
  $image = '';
} elseif ($pickedCover !== '' && $pickedCover !== $prevCover) {
  $old = upload_path($prevCover);
  if ($old !== '' && is_file($old)) @unlink($old);
  $image = $pickedCover;
}

/* Conserve la date de création si l'activité existe déjà. */
$created = date('c');
foreach ($activites as $it) {
  if (($it['slug'] ?? '') === $slug && !empty($it['created'])) { $created = $it['created']; break; }
}

$record = array(
  'slug'        => $slug,
  'title'       => $title,
  'description' => $description,
  'image'       => $image,
  'jour'        => $jour,
  'horaire'     => $horaire,
  'public'      => $public,
  'ordre'       => $ordre,
  'published'   => $published,
  'created'     => $created,
  'updated'     => date('c'),
);

/* Remplace ou ajoute. */
$found = false;
foreach ($activites as $i => $it) {
  if (($it['slug'] ?? '') === $slug) { $activites[$i] = $record; $found = true; break; }
}
if (!$found) $activites[] = $record;

save_activites($activites);
header('Location: activites.php?ok=saved&slug=' . urlencode($slug));
exit;
