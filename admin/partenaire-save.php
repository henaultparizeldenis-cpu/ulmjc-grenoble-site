<?php
/* Enregistrement d'un partenaire. Calqué sur admin/save.php, sans corps HTML.
   Identifiant = « id » (slug du nom), champs nom, url, ordre, published, logo
   (upload cover_file OU médiathèque « cover »). CSRF + clean_utf8 conservés. */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) {
  header('Location: partenaires.php'); exit;
}

$nom       = clean_utf8(trim($_POST['nom'] ?? ''));
$ordre     = (int)($_POST['ordre'] ?? 0);
$published = !empty($_POST['published']);
$origId    = preg_replace('/[^a-z0-9\-]/', '', $_POST['orig_id'] ?? '');
$pickedLogo = media_valid_src($_POST['cover'] ?? '');

// URL : on n'accepte qu'un lien http(s) ou vide.
$url = trim($_POST['url'] ?? '');
if ($url !== '' && !preg_match('#^https?://#i', $url)) { $url = 'https://' . $url; }
if ($url !== '' && !preg_match('#^https?://[^\s"\'<>]+$#i', $url)) { $url = ''; }

// Le nom est obligatoire.
if ($nom === '') { header('Location: partenaire-edit.php' . ($origId ? '?id=' . $origId : '')); exit; }

$partenaires = load_partenaires();

// Retrouve l'existant + son ancien logo (pour nettoyage)
$prevLogo = '';
$existing = null;
if ($origId) { foreach ($partenaires as $it) { if (($it['id'] ?? '') === $origId) { $existing = $it; $prevLogo = $it['logo'] ?? ''; break; } } }

/* Id : stable en édition, généré à la création (unicité par type via unique_slug
   qui s'appuie sur le champ 'slug' — on aligne donc id/slug le temps du calcul). */
if ($existing) {
  $id = $origId;
} else {
  $base = slugify($nom);
  // unique_slug compare le champ 'slug' ; nos partenaires utilisent 'id' → on adapte.
  $ids = array();
  foreach ($partenaires as $it) $ids[] = $it['id'] ?? '';
  $id = $base; $i = 2;
  while (in_array($id, $ids, true)) { $id = $base . '-' . $i; $i++; }
}

/* Logo. Priorité : (1) upload ; (2) retrait explicite ; (3) médiathèque ; (4) ancien. */
$logo = $prevLogo;
if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK && is_uploaded_file($_FILES['cover_file']['tmp_name'])) {
  $tmp = $_FILES['cover_file']['tmp_name'];
  if (@getimagesize($tmp)) {
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
    $fname = 'logo-' . $id . '-' . time() . '.jpg';
    $dest  = UPLOAD_DIR . '/' . $fname;
    if (optimize_image($tmp, $dest)) {
      $old = upload_path($prevLogo);
      if ($old !== '' && is_file($old)) @unlink($old);
      $logo = UPLOAD_URL . '/' . $fname;
    }
  }
} elseif (!empty($_POST['cover_remove'])) {
  $old = upload_path($prevLogo);
  if ($old !== '' && is_file($old)) @unlink($old);
  $logo = '';
} elseif ($pickedLogo !== '' && $pickedLogo !== $prevLogo) {
  $old = upload_path($prevLogo);
  if ($old !== '' && is_file($old)) @unlink($old);
  $logo = $pickedLogo;
}

$record = array(
  'id'        => $id,
  'nom'       => $nom,
  'logo'      => $logo,
  'url'       => $url,
  'ordre'     => $ordre,
  'published' => $published,
);

/* Remplace ou ajoute. */
$found = false;
foreach ($partenaires as $i => $it) {
  if (($it['id'] ?? '') === $id) { $partenaires[$i] = $record; $found = true; break; }
}
if (!$found) $partenaires[] = $record;

save_partenaires($partenaires);
header('Location: partenaires.php?ok=saved');
exit;
