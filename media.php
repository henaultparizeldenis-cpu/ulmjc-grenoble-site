<?php
/* Sert une image importée depuis le dossier des uploads.
   Ce dossier peut vivre HORS du dépôt (ulmjc-data/uploads) pour survivre aux
   déploiements, il n'est donc pas servable directement, d'où ce relais.
   Public (les médias s'affichent sur le site) mais verrouillé :
   nom de fichier seul (pas de « ../ »), extensions en liste blanche, aucune
   exécution, on ne fait que renvoyer des octets avec le bon type MIME.
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
  'pdf' => 'application/pdf',   // pièces jointes : fiches de poste des offres d'emploi
);
if (!isset($types[$ext])) { http_response_code(404); exit; }

$path = UPLOAD_DIR . '/' . $name;
if (!is_file($path)) { http_response_code(404); exit; }

/* « immutable » promettait au navigateur que le contenu de cette URL ne
   changerait JAMAIS, et le dispensait donc de redemander quoi que ce soit
   pendant un an. C'etait faux : le nom du fichier est fixe, mais son contenu
   change quand la mediatheque fait pivoter une photo (admin/media-rotate.php
   reecrit le fichier sur place). Resultat, la rotation semblait ne pas tenir :
   a la reouverture, le navigateur ressortait l'ancienne image de son cache.
   On garde donc le cache, mais on impose la revalidation : l'ETag ci-dessous
   repond 304 en quelques octets tant que l'image n'a pas bouge, et renvoie la
   nouvelle des qu'elle a change. */
define('CACHE_MEDIA', 'public, max-age=0, must-revalidate');

$mtime = @filemtime($path);
$size  = @filesize($path);
$etag  = '"' . dechex((int) $mtime) . '-' . dechex((int) $size) . '"';

$inm = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : '';
$ims = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 0;
if (($inm !== '' && $inm === $etag) || ($ims && $mtime && $ims >= $mtime)) {
  header('ETag: ' . $etag);
  header('Cache-Control: ' . CACHE_MEDIA);
  http_response_code(304);
  exit;
}

header('Content-Type: ' . $types[$ext]);
/* Un PDF s'ouvre dans le lecteur du navigateur plutôt que de se télécharger
   d'office ; le nom d'origine n'ayant aucun sens (doc-<horodatage>.pdf), on
   force un nom parlant. « inline » laisse le visiteur choisir d'enregistrer. */
if ($ext === 'pdf') { header('Content-Disposition: inline; filename="fiche-de-poste.pdf"'); }
header('Content-Length: ' . $size);
header('Cache-Control: ' . CACHE_MEDIA);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int) $mtime) . ' GMT');
header('ETag: ' . $etag);
header('X-Content-Type-Options: nosniff');
readfile($path);
