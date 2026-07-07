<?php
/* Upload AJAX d'une image (éditeur / médiathèque) : optimise et renvoie son URL.
   Basé sur mohamed-cms/site/admin/upload.php (session ulmjc_admin). */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) { http_response_code(403); echo json_encode(array('error' => 'Non connecté')); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) { http_response_code(403); echo json_encode(array('error' => 'Jeton invalide')); exit; }

// POST trop volumineux : PHP vide $_FILES alors qu'on a bien envoyé des octets
if (empty($_FILES['file']) && !empty($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
  echo json_encode(array('error' => 'Photo trop lourde pour le serveur.')); exit;
}
if (empty($_FILES['file'])) { echo json_encode(array('error' => 'Aucun fichier reçu')); exit; }
$err = $_FILES['file']['error'];
if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
  echo json_encode(array('error' => 'Photo trop lourde.')); exit;
}
if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['file']['tmp_name'])) {
  echo json_encode(array('error' => "Échec de l'upload (code $err)")); exit;
}
$tmp = $_FILES['file']['tmp_name'];
if (!@getimagesize($tmp)) { echo json_encode(array('error' => "Ce fichier n'est pas une image")); exit; }

if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
$fname = 'img-' . time() . '-' . mt_rand(1000, 9999) . '.jpg';
$dest  = UPLOAD_DIR . '/' . $fname;

if (optimize_image($tmp, $dest)) {
  echo json_encode(array('url' => UPLOAD_URL . '/' . $fname));
} else {
  echo json_encode(array('error' => "Échec du traitement de l'image"));
}
