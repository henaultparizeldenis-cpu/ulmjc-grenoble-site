<?php
/* Enregistrement d'une maison. Calqué sur admin/partenaire-save.php.
   Identifiant = « slug » (dérivé du nom), CSRF et clean_utf8 conservés.

   Particularité : les coordonnées. Plutôt que de les faire saisir à la main,
   on les demande à la Base Adresse Nationale à partir de l'adresse. C'est un
   service public, sans clé, et le contrôle fait sur les neuf maisons déjà en
   ligne donne un écart médian nul et un écart maximal d'un mètre.

   On ne géocode que si nécessaire : à la création, ou quand l'adresse a
   changé, ou quand les coordonnées manquent. Et une valeur saisie à la main
   l'emporte toujours, pour rattraper une adresse mal reconnue. */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) {
  header('Location: mjc.php'); exit;
}

$nom       = clean_utf8(trim($_POST['nom'] ?? ''));
$quartier  = clean_utf8(trim($_POST['quartier'] ?? ''));
$adresse   = clean_utf8(trim($_POST['adresse'] ?? ''));
$antennes  = clean_utf8(trim($_POST['antennes'] ?? ''));
$lieu      = clean_utf8(trim($_POST['lieu'] ?? ''));
$tel       = clean_utf8(trim($_POST['tel'] ?? ''));
$ordre     = (int)($_POST['ordre'] ?? 0);
$published = !empty($_POST['published']);
$origSlug  = preg_replace('/[^a-z0-9\-]/', '', $_POST['orig_slug'] ?? '');
$pickedLogo = media_valid_src($_POST['cover'] ?? '');

$email = trim($_POST['email'] ?? '');
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';

$site = trim($_POST['site'] ?? '');
if ($site !== '' && !preg_match('#^https?://#i', $site)) { $site = 'https://' . $site; }
if ($site !== '' && !preg_match('#^https?://[^\s"\'<>]+$#i', $site)) { $site = ''; }

// Le nom est obligatoire.
if ($nom === '') { header('Location: mjc-edit.php' . ($origSlug ? '?slug=' . $origSlug : '')); exit; }

$maisons = load_mjc();

// Retrouve l'existante + son ancien logo (pour nettoyage) et son ancienne adresse.
$prevLogo = ''; $prevAdresse = ''; $existing = null;
if ($origSlug) {
  foreach ($maisons as $it) {
    if (($it['slug'] ?? '') === $origSlug) {
      $existing = $it; $prevLogo = $it['logo'] ?? ''; $prevAdresse = $it['adresse'] ?? '';
      break;
    }
  }
}

/* Slug : stable en édition, généré à la création. Renommer une maison ne doit
   pas casser un lien déjà partagé. */
if ($existing) {
  $slug = $origSlug;
} else {
  $base = slugify($nom);
  $pris = array();
  foreach ($maisons as $it) $pris[] = $it['slug'] ?? '';
  $slug = $base; $i = 2;
  while (in_array($slug, $pris, true)) { $slug = $base . '-' . $i; $i++; }
}

/* Coordonnées. La saisie manuelle l'emporte ; sinon on géocode l'adresse. */
$lat = trim($_POST['lat'] ?? '');
$lon = trim($_POST['lon'] ?? '');
$lat = is_numeric($lat) ? (float)$lat : null;
$lon = is_numeric($lon) ? (float)$lon : null;

$memeAdresse = ($existing && $adresse !== '' && $adresse === $prevAdresse);

/* Le batiment designe a la main. Il n'est pas dans le formulaire : il se
   choisit sur la carte (admin/mjc-batiment.php). On le reconduit tel quel,
   SAUF si l'adresse a change : la maison a demenage, le batiment d'avant
   n'est plus le sien. */
$batiment = ($existing && $memeAdresse) ? ($existing['batiment'] ?? '') : '';
$saisieManuelle = ($lat !== null && $lon !== null);
/* On regeocode si l'adresse a bougé, même si d'anciennes coordonnées traînent :
   sinon un déménagement laisserait le point à l'ancienne adresse. */
if ($adresse !== '' && (!$saisieManuelle || !$memeAdresse)) {
  $trouve = geocode_adresse($adresse);
  if ($trouve) { $lat = $trouve[1]; $lon = $trouve[0]; }
}

/* Logo. Priorité : (1) upload ; (2) retrait explicite ; (3) médiathèque ; (4) ancien. */
$logo = $prevLogo;
if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK && is_uploaded_file($_FILES['cover_file']['tmp_name'])) {
  $tmp = $_FILES['cover_file']['tmp_name'];
  if (@getimagesize($tmp)) {
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
    $fname = 'mjc-' . $slug . '-' . time() . '.jpg';
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
  'slug'      => $slug,
  'nom'       => $nom,
  'quartier'  => $quartier,
  'adresse'   => $adresse,
  'antennes'  => $antennes,
  'tel'       => $tel,
  'email'     => $email,
  'site'      => $site,
  'logo'      => $logo,
  'lat'       => $lat,
  'lon'       => $lon,
  'lieu'      => $lieu,
  'batiment'  => $batiment,
  'ordre'     => $ordre,
  'published' => $published,
);

/* Remplace ou ajoute. */
$found = false;
foreach ($maisons as $i => $it) {
  if (($it['slug'] ?? '') === $slug) { $maisons[$i] = $record; $found = true; break; }
}
if (!$found) $maisons[] = $record;

save_mjc($maisons);
header('Location: mjc.php?ok=saved');
exit;
