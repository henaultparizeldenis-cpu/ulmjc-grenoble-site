<?php
/* Diagnostic d'import d'images — OUTIL TEMPORAIRE.
   Affiche l'état réel du serveur : dossiers, droits d'écriture, bibliothèque GD,
   limites PHP, et effectue un vrai test d'écriture dans le dossier des images.
   Protégé par la session admin (require_login) : rien n'est exposé publiquement.
   À SUPPRIMER une fois le problème résolu. */
require_once __DIR__ . '/auth.php';
require_login();

header('Content-Type: text/plain; charset=utf-8');

function ligne($cle, $valeur) { printf("%-34s %s\n", $cle, $valeur); }
function ouinon($b) { return $b ? 'OUI' : 'NON  <-- PROBLEME'; }

echo "=== DIAGNOSTIC D'IMPORT D'IMAGES ===\n\n";

echo "--- Dossiers ---\n";
ligne('DATA_DIR',            DATA_DIR);
ligne('  existe',            ouinon(is_dir(DATA_DIR)));
ligne('  accessible en ecriture', ouinon(is_writable(DATA_DIR)));
ligne('UPLOAD_DIR',          UPLOAD_DIR);
ligne('  existe',            ouinon(is_dir(UPLOAD_DIR)));
ligne('  accessible en ecriture', ouinon(is_dir(UPLOAD_DIR) && is_writable(UPLOAD_DIR)));
if (is_dir(UPLOAD_DIR)) {
  ligne('  droits (octal)',  substr(sprintf('%o', @fileperms(UPLOAD_DIR)), -4));
  ligne('  proprietaire',    function_exists('posix_getpwuid') && @fileowner(UPLOAD_DIR) !== false
                             ? (@posix_getpwuid(@fileowner(UPLOAD_DIR))['name'] ?? @fileowner(UPLOAD_DIR))
                             : (string)@fileowner(UPLOAD_DIR));
  ligne('  nb de fichiers',  (string)max(0, count(@scandir(UPLOAD_DIR) ?: array()) - 2));
}
ligne('utilisateur PHP',     function_exists('posix_geteuid') && function_exists('posix_getpwuid')
                             ? (@posix_getpwuid(posix_geteuid())['name'] ?? '?') : get_current_user());

echo "\n--- Bibliotheque images (GD) ---\n";
ligne('imagecreatetruecolor', ouinon(function_exists('imagecreatetruecolor')));
ligne('imagecreatefromjpeg',  ouinon(function_exists('imagecreatefromjpeg')));
ligne('imagecreatefrompng',   ouinon(function_exists('imagecreatefrompng')));
ligne('imagejpeg',            ouinon(function_exists('imagejpeg')));
ligne('getimagesize',         ouinon(function_exists('getimagesize')));

echo "\n--- Limites PHP ---\n";
foreach (array('upload_max_filesize','post_max_size','memory_limit','max_execution_time','file_uploads') as $k) {
  ligne($k, (string)ini_get($k));
}
ligne('dossier temporaire', (string)(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()));
ligne('  ecriture temporaire', ouinon(is_writable(ini_get('upload_tmp_dir') ?: sys_get_temp_dir())));

echo "\n--- TEST REEL D'ECRITURE ---\n";
if (!is_dir(UPLOAD_DIR)) {
  echo "UPLOAD_DIR absent : tentative de creation...\n";
  ligne('  creation', ouinon(@mkdir(UPLOAD_DIR, 0775, true)));
}
$test = rtrim(UPLOAD_DIR, '/\\') . '/_test-' . time() . '.txt';
$ecrit = @file_put_contents($test, 'test');
ligne('ecriture fichier texte', $ecrit !== false ? 'OUI (' . $ecrit . " octets)" : 'NON  <-- PROBLEME');
if ($ecrit !== false) { @unlink($test); }

if (function_exists('imagecreatetruecolor')) {
  $img = @imagecreatetruecolor(40, 20);
  $jpg = rtrim(UPLOAD_DIR, '/\\') . '/_test-' . time() . '.jpg';
  $ok  = $img ? @imagejpeg($img, $jpg, 80) : false;
  ligne('ecriture image JPEG', ouinon($ok));
  if ($img) @imagedestroy($img);
  if ($ok) { @unlink($jpg); }
}

echo "\n--- Dernieres erreurs PHP ---\n";
$log = @ini_get('error_log');
ligne('journal', (string)($log ?: '(non defini)'));
if ($log && is_readable($log)) {
  $lines = @file($log);
  if ($lines) { echo implode('', array_slice($lines, -12)); }
}

echo "\n=== FIN ===\n";
