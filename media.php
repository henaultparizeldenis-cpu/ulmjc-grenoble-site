<?php
/* Sert une image importée depuis le dossier des uploads.
   Ce dossier peut vivre HORS du dépôt (ulmjc-data/uploads) pour survivre aux
   déploiements — il n'est donc pas servable directement, d'où ce relais.
   Public (les médias s'affichent sur le site) mais verrouillé :
   nom de fichier seul (pas de « ../ »), extensions en liste blanche, aucune
   exécution — on ne fait que renvoyer des octets avec le bon type MIME.
   Basé sur mohamed-cms/site/media.php (inchangé hormis les chemins de config). */
require_once __DIR__ . '/inc/config.php';

$f    = isset($_GET['f']) ? (string) $_GET['f'] : '';
$name = basename($f);
if ($name === '' || $name[0] === '.' || $name !== $f) { http_response_code(404); exit; }

$ext   = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$types = array(
  'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
  'webp' => 'image/webp', 'gif' => 'image/gif',
  'mp4' => 'video/mp4', 'webm' => 'video/webm',
);
if (!isset($types[$ext])) { http_response_code(404); exit; }

$path = UPLOAD_DIR . '/' . $name;
if (!is_file($path)) { http_response_code(404); exit; }

$mtime = @filemtime($path);
$size  = @filesize($path);
$etag  = '"' . dechex((int) $mtime) . '-' . dechex((int) $size) . '"';

$inm = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : '';
$ims = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 0;
if (($inm !== '' && $inm === $etag) || ($ims && $mtime && $ims >= $mtime)) {
  header('ETag: ' . $etag);
  header('Cache-Control: public, max-age=31536000, immutable');
  http_response_code(304);
  exit;
}

header('Content-Type: ' . $types[$ext]);
header('Content-Length: ' . $size);
header('Cache-Control: public, max-age=31536000, immutable');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int) $mtime) . ' GMT');
header('ETag: ' . $etag);
header('X-Content-Type-Options: nosniff');
readfile($path);
