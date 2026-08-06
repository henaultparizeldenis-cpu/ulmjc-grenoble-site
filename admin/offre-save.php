<?php
/* Enregistrement d'une offre d'emploi. Calqué sur admin/billet-save.php.
   Mêmes garde-fous : POST + CSRF obligatoires, clean_utf8() sur les textes,
   sanitize_body() sur la description, slug filtré et stable en édition.
   Pas d'image : une offre n'a pas de couverture. */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) {
  header('Location: emplois.php'); exit;
}

$title     = clean_utf8(trim($_POST['title'] ?? ''));
$date      = trim($_POST['date'] ?? '');
$limite    = trim($_POST['date_limite'] ?? '');
$temps     = clean_utf8(trim($_POST['temps'] ?? ''));
$lieu      = clean_utf8(trim($_POST['lieu'] ?? ''));
$excerpt   = clean_utf8(trim($_POST['excerpt'] ?? ''));
$contact   = clean_utf8(trim($_POST['contact'] ?? ''));
$body      = sanitize_body($_POST['body'] ?? '');
$published = !empty($_POST['published']);
$origSlug  = preg_replace('/[^a-z0-9\-]/', '', $_POST['orig_slug'] ?? '');

// Type de contrat validé contre la liste FERMÉE ('' si inconnu/absent).
$contrat = array_key_exists($_POST['contrat'] ?? '', emploi_contrats()) ? (string)$_POST['contrat'] : '';

// L'intitulé du poste est obligatoire.
if ($title === '') { header('Location: offre-edit.php' . ($origSlug ? '?slug=' . $origSlug : '')); exit; }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');
// Date limite facultative : vidée si absente ou si elle n'existe pas au calendrier.
$limite = emploi_deadline(array('date_limite' => $limite));

// Longueurs bornées (miroir des maxlength du formulaire).
$temps   = mb_substr($temps, 0, 80);
$lieu    = mb_substr($lieu, 0, 120);
$excerpt = mb_substr($excerpt, 0, 200);
$contact = mb_substr($contact, 0, 400);

/* Fiche de poste PDF : on n'accepte qu'un chemin uploads/….pdf existant
   (doc_valid_src) — jamais une valeur brute du formulaire. */
$fiche = doc_valid_src($_POST['fiche'] ?? '');

$offres = load_emplois();

/* Si le PDF a été remplacé ou retiré, on efface l'ancien fichier du disque
   pour ne pas accumuler des documents orphelins hors dépôt. */
if ($origSlug) {
  foreach ($offres as $it) {
    if (($it['slug'] ?? '') === $origSlug && !empty($it['fiche']) && $it['fiche'] !== $fiche) {
      $old = upload_path($it['fiche']);
      if ($old !== '' && is_file($old)) @unlink($old);
      break;
    }
  }
}

/* Slug : stable en édition, généré à la création. */
if ($origSlug && find_emploi($origSlug)) {
  $slug = $origSlug;
} else {
  $slug = unique_slug('emplois', slugify($title));
}

/* Conserve la date de création si l'offre existe déjà. */
$created = date('c');
foreach ($offres as $it) {
  if (($it['slug'] ?? '') === $slug && !empty($it['created'])) { $created = $it['created']; break; }
}

$record = array(
  'slug'        => $slug,
  'title'       => $title,
  'contrat'     => $contrat,
  'temps'       => $temps,
  'lieu'        => $lieu,
  'date'        => $date,
  'date_limite' => $limite,
  'excerpt'     => $excerpt,
  'body'        => $body,
  'contact'     => $contact,
  'fiche'       => $fiche,
  'published'   => $published,
  'created'     => $created,
  'updated'     => date('c'),
);

/* Remplace ou ajoute. */
$found = false;
foreach ($offres as $i => $it) {
  if (($it['slug'] ?? '') === $slug) { $offres[$i] = $record; $found = true; break; }
}
if (!$found) $offres[] = $record;

save_emplois($offres);
header('Location: emplois.php?ok=saved&slug=' . urlencode($slug));
exit;
