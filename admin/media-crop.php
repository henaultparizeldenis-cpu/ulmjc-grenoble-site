<?php
/* Médiathèque : rogner et/ou redimensionner une image importée.

   Répond au cas « ma photo est coupée » : les vignettes du site sont recadrées
   automatiquement (16:10 pour les cartes, 3:2 pour les bandeaux) TOUJOURS AU
   CENTRE. Quand le sujet n'est pas au centre, il disparaît. En rognant soi-même
   à l'avance, on décide de ce qui reste visible.

   Opération DESTRUCTIVE : elle réécrit le fichier, donc se répercute partout où
   la photo est utilisée. Réservée aux fichiers de uploads/ (ceux de images/ sont
   versionnés et seraient écrasés au prochain déploiement).

   Sécurité : session admin + CSRF ; chemin validé par media_valid_src() ;
   coordonnées bornées aux dimensions réelles de l'image. */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) { http_response_code(403); echo json_encode(array('error' => 'Non connecté')); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) { http_response_code(403); echo json_encode(array('error' => 'Jeton invalide')); exit; }
if (!function_exists('imagecreatetruecolor')) { echo json_encode(array('error' => "Le serveur ne sait pas retoucher les images.")); exit; }

$src  = media_valid_src($_POST['src'] ?? '');
$path = upload_path($src);
if ($path === '' || !is_file($path)) {
  echo json_encode(array('error' => 'Seules les photos importées peuvent être retouchées.')); exit;
}

$info = @getimagesize($path);
if (!$info) { echo json_encode(array('error' => "Ce fichier n'est pas une image.")); exit; }
list($W, $H) = $info;

switch ($info[2]) {
  case IMAGETYPE_JPEG: $im = @imagecreatefromjpeg($path); break;
  case IMAGETYPE_PNG:  $im = @imagecreatefrompng($path);  break;
  case IMAGETYPE_WEBP: $im = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false; break;
  case IMAGETYPE_GIF:  $im = @imagecreatefromgif($path);  break;
  default: $im = false;
}
if (!$im) { echo json_encode(array('error' => "Format d'image non pris en charge.")); exit; }

/* --- 1. Rognage (facultatif) --- */
$hasCrop = isset($_POST['x'], $_POST['y'], $_POST['w'], $_POST['h']);
if ($hasCrop) {
  // Coordonnées bornées à l'image : une valeur aberrante ne peut pas faire planter GD.
  $x = max(0, min($W - 1, (int) $_POST['x']));
  $y = max(0, min($H - 1, (int) $_POST['y']));
  $w = max(1, min($W - $x, (int) $_POST['w']));
  $h = max(1, min($H - $y, (int) $_POST['h']));

  if ($w < 20 || $h < 20) { imagedestroy($im); echo json_encode(array('error' => 'Sélection trop petite (20 px minimum).')); exit; }

  $out = imagecreatetruecolor($w, $h);
  imagefilledrectangle($out, 0, 0, $w, $h, imagecolorallocate($out, 255, 255, 255));
  imagecopy($out, $im, 0, 0, $x, $y, $w, $h);
  imagedestroy($im);
  $im = $out; $W = $w; $H = $h;
}

/* --- 2. Redimensionnement (facultatif) --- */
$maxW = (int) ($_POST['maxw'] ?? 0);
if ($maxW > 0 && $maxW < $W) {
  $nh = (int) round($H * $maxW / $W);
  $out = imagecreatetruecolor($maxW, $nh);
  imagefilledrectangle($out, 0, 0, $maxW, $nh, imagecolorallocate($out, 255, 255, 255));
  imagecopyresampled($out, $im, 0, 0, 0, 0, $maxW, $nh, $W, $H);
  imagedestroy($im);
  $im = $out; $W = $maxW; $H = $nh;
}

if (!$hasCrop && $maxW <= 0) { imagedestroy($im); echo json_encode(array('error' => 'Rien à faire.')); exit; }

/* Écriture atomique : fichier temporaire puis remplacement, pour qu'une
   interruption ne laisse jamais une image corrompue à la place de l'originale. */
$tmp = $path . '.tmp';
switch ($info[2]) {
  case IMAGETYPE_PNG:  $ok = @imagepng($im, $tmp); break;
  case IMAGETYPE_WEBP: $ok = function_exists('imagewebp') ? @imagewebp($im, $tmp, 88) : false; break;
  case IMAGETYPE_GIF:  $ok = @imagegif($im, $tmp); break;
  default:             $ok = @imagejpeg($im, $tmp, IMG_QUALITY); break;
}
imagedestroy($im);

if (!$ok || !is_file($tmp)) { @unlink($tmp); echo json_encode(array('error' => "Impossible d'enregistrer l'image.")); exit; }
if (!@rename($tmp, $path)) { @unlink($tmp); echo json_encode(array('error' => 'Impossible de remplacer le fichier.')); exit; }
@chmod($path, 0644);

echo json_encode(array('ok' => true, 'v' => (string) time(), 'w' => $W, 'h' => $H));
