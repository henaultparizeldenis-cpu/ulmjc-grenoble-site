<?php
/* Médiathèque : fait pivoter une image de 90° (quart de tour à gauche ou à droite).
   Cas typique : une photo prise au téléphone qui s'affiche couchée.

   La rotation est DESTRUCTIVE — elle réécrit le fichier — et se répercute donc
   partout où la photo est utilisée. C'est voulu : on redresse une fois, et
   l'image est correcte sur tout le site.

   Restreinte aux fichiers de uploads/ : les images de images/ sont versionnées
   dans le dépôt et seraient de toute façon écrasées au prochain déploiement.

   Sécurité : session admin + CSRF ; le chemin passe par media_valid_src(), donc
   pas de traversée de dossier ; on ne traite que de vraies images. */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) { http_response_code(403); echo json_encode(array('error' => 'Non connecté')); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) { http_response_code(403); echo json_encode(array('error' => 'Jeton invalide')); exit; }

if (!function_exists('imagerotate')) {
  echo json_encode(array('error' => "Le serveur ne sait pas faire pivoter les images.")); exit;
}

$src = media_valid_src($_POST['src'] ?? '');
$path = upload_path($src);   // vide si l'image n'est pas dans uploads/
if ($path === '' || !is_file($path)) {
  echo json_encode(array('error' => "Seules les photos importées peuvent être pivotées.")); exit;
}

$sens = ($_POST['sens'] ?? '') === 'gauche' ? 'gauche' : 'droite';
$angle = $sens === 'gauche' ? 90 : -90;   // GD tourne dans le sens antihoraire

$info = @getimagesize($path);
if (!$info) { echo json_encode(array('error' => "Ce fichier n'est pas une image.")); exit; }

switch ($info[2]) {
  case IMAGETYPE_JPEG: $im = @imagecreatefromjpeg($path); break;
  case IMAGETYPE_PNG:  $im = @imagecreatefrompng($path);  break;
  case IMAGETYPE_WEBP: $im = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false; break;
  case IMAGETYPE_GIF:  $im = @imagecreatefromgif($path);  break;
  default: $im = false;
}
if (!$im) { echo json_encode(array('error' => "Format d'image non pris en charge.")); exit; }

$rot = @imagerotate($im, $angle, 0);
if (!$rot) { imagedestroy($im); echo json_encode(array('error' => 'Échec de la rotation.')); exit; }

/* Écriture atomique : on écrit à côté puis on remplace. Sans ça, une écriture
   interrompue laisserait une image corrompue à la place de l'originale. */
$tmp = $path . '.tmp';
switch ($info[2]) {
  case IMAGETYPE_PNG:  $ok = @imagepng($rot, $tmp); break;
  case IMAGETYPE_WEBP: $ok = function_exists('imagewebp') ? @imagewebp($rot, $tmp, 88) : false; break;
  case IMAGETYPE_GIF:  $ok = @imagegif($rot, $tmp); break;
  default:             $ok = @imagejpeg($rot, $tmp, IMG_QUALITY); break;
}
imagedestroy($im); imagedestroy($rot);

if (!$ok || !is_file($tmp)) { @unlink($tmp); echo json_encode(array('error' => "Impossible d'enregistrer l'image pivotée.")); exit; }
if (!@rename($tmp, $path)) { @unlink($tmp); echo json_encode(array('error' => 'Impossible de remplacer le fichier.')); exit; }
@chmod($path, 0644);

/* Le navigateur garde l'ancienne image en cache : on renvoie un jeton qui sert
   à forcer son rechargement côté interface. */
echo json_encode(array('ok' => true, 'v' => (string) time()));
