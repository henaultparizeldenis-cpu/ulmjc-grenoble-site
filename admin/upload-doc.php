<?php
/* Upload AJAX d'un DOCUMENT PDF (fiche de poste d'une offre d'emploi).
   Pendant de upload.php, qui ne gère que les images.

   Sécurité : session admin + CSRF ; on ne se fie NI à l'extension NI au type
   déclaré par le navigateur (tous deux falsifiables) : on lit les premiers
   octets du fichier, qui doivent commencer par « %PDF- ». Le fichier est
   renommé (doc-<horodatage>-<aléa>.pdf) et déposé dans UPLOAD_DIR, dont le
   .htaccess coupe toute exécution. */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) { http_response_code(403); echo json_encode(array('error' => 'Non connecté')); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) { http_response_code(403); echo json_encode(array('error' => 'Jeton invalide')); exit; }

// POST trop volumineux : PHP vide $_FILES alors que des octets ont été envoyés.
if (empty($_FILES['file']) && !empty($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
  echo json_encode(array('error' => 'Document trop lourd pour le serveur.')); exit;
}
if (empty($_FILES['file'])) { echo json_encode(array('error' => 'Aucun fichier reçu')); exit; }

$err = $_FILES['file']['error'];
if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
  echo json_encode(array('error' => 'Document trop lourd.')); exit;
}
if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['file']['tmp_name'])) {
  echo json_encode(array('error' => "Échec de l'envoi (code $err)")); exit;
}

$tmp = $_FILES['file']['tmp_name'];

// Le contenu doit réellement être un PDF (signature %PDF-), pas seulement le nom.
$head = (string) @file_get_contents($tmp, false, null, 0, 5);
if ($head !== '%PDF-') {
  echo json_encode(array('error' => "Ce fichier n'est pas un PDF.")); exit;
}

// Garde-fou de taille, au-delà des limites PHP (un PDF de fiche de poste est léger).
$max = 12 * 1024 * 1024;
if ((int) @filesize($tmp) > $max) {
  echo json_encode(array('error' => 'PDF trop lourd (12 Mo maximum).')); exit;
}

if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
$fname = 'doc-' . time() . '-' . mt_rand(1000, 9999) . '.pdf';
$dest  = UPLOAD_DIR . '/' . $fname;

if (@move_uploaded_file($tmp, $dest)) {
  @chmod($dest, 0644);
  echo json_encode(array('url' => UPLOAD_URL . '/' . $fname));
} else {
  echo json_encode(array('error' => "Impossible d'enregistrer le document."));
}
