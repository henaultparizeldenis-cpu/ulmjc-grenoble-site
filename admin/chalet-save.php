<?php
/* Gestion AJAX des Photos du chalet (galerie par catégories).
   Inspiré de mohamed-cms/site/admin/section-media.php (persistance immédiate) et
   du sélecteur openMediaPicker du pilote. Écrit via save_gallery() (helpers dédiés,
   structure dict catégorie→liste de chemins). CSRF obligatoire.

   Actions (POST, JSON en retour) :
   - upload  : optimise un fichier (uploads/ hors dépôt) et l'AJOUTE à la catégorie ;
   - select  : AJOUTE une image existante de la médiathèque à la catégorie ;
   - remove  : RETIRE une image de la catégorie (ne supprime PAS le fichier, qui peut
               servir ailleurs ; le nettoyage médiathèque reste manuel) ;
   - reorder : REMPLACE l'ordre de la catégorie par la liste « order[] » fournie.
   Chaque réponse renvoie la liste à jour de la catégorie (« items ») pour rafraîchir l'UI. */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) { http_response_code(403); echo json_encode(array('error' => 'Non connecté')); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) { http_response_code(403); echo json_encode(array('error' => 'Jeton invalide')); exit; }

$action = $_POST['action'] ?? '';
$cat    = $_POST['cat'] ?? '';
$cats   = chalet_categories();
if (!array_key_exists($cat, $cats)) { echo json_encode(array('error' => 'Catégorie inconnue')); exit; }

$gallery = load_gallery();
$list    = isset($gallery[$cat]) ? $gallery[$cat] : array();

if ($action === 'upload') {
  if (empty($_FILES['file']) && !empty($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
    echo json_encode(array('error' => 'Photo trop lourde pour le serveur.')); exit;
  }
  if (empty($_FILES['file'])) { echo json_encode(array('error' => 'Aucun fichier reçu')); exit; }
  $err = $_FILES['file']['error'];
  if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) { echo json_encode(array('error' => 'Photo trop lourde.')); exit; }
  if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['file']['tmp_name'])) { echo json_encode(array('error' => "Échec de l'upload (code $err)")); exit; }
  $tmp = $_FILES['file']['tmp_name'];
  if (!@getimagesize($tmp)) { echo json_encode(array('error' => "Ce fichier n'est pas une image")); exit; }
  if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
  $fname = 'chalet-' . $cat . '-' . time() . '-' . mt_rand(1000, 9999) . '.jpg';
  $dest  = UPLOAD_DIR . '/' . $fname;
  if (!optimize_image($tmp, $dest)) { echo json_encode(array('error' => "Échec du traitement de l'image")); exit; }
  $src = UPLOAD_URL . '/' . $fname;
  if (!in_array($src, $list, true)) $list[] = $src;
  $gallery[$cat] = $list;
  save_gallery($gallery);
  echo json_encode(array('ok' => true, 'items' => $gallery[$cat])); exit;
}

if ($action === 'select') {
  // Ajout d'une (ou plusieurs) image(s) existante(s) de la médiathèque.
  $srcs = isset($_POST['src']) ? (array) $_POST['src'] : array();
  foreach ($srcs as $s) {
    $v = gallery_valid_src($s);
    if ($v !== '' && !in_array($v, $list, true)) $list[] = $v;
  }
  $gallery[$cat] = $list;
  save_gallery($gallery);
  echo json_encode(array('ok' => true, 'items' => $gallery[$cat])); exit;
}

if ($action === 'remove') {
  $src = gallery_valid_src($_POST['src'] ?? '');
  $list = array_values(array_filter($list, function ($s) use ($src) { return $s !== $src; }));
  $gallery[$cat] = $list;
  save_gallery($gallery);
  echo json_encode(array('ok' => true, 'items' => $gallery[$cat])); exit;
}

if ($action === 'reorder') {
  // Nouvel ordre complet : on ne garde que les chemins DÉJÀ présents dans la catégorie
  // (sécurité : on ne peut pas injecter d'image via reorder), sans doublon.
  $order = isset($_POST['order']) ? (array) $_POST['order'] : array();
  $set = $list; // chemins actuellement valides
  $new = array();
  foreach ($order as $s) {
    $v = gallery_valid_src($s);
    if ($v !== '' && in_array($v, $set, true) && !in_array($v, $new, true)) $new[] = $v;
  }
  // On complète avec d'éventuels chemins oubliés par le client (robustesse).
  foreach ($set as $s) { if (!in_array($s, $new, true)) $new[] = $s; }
  $gallery[$cat] = $new;
  save_gallery($gallery);
  echo json_encode(array('ok' => true, 'items' => $gallery[$cat])); exit;
}

echo json_encode(array('error' => 'Action inconnue'));
