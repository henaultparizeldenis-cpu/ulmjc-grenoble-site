<?php
/* Enregistre le bâtiment désigné pour une maison, ou l'efface.

   L'identifiant est celui de la BD TOPO, privé de son préfixe et de ses zéros
   de tête (voir geo/bati.json). On ne garde que des chiffres : rien d'autre
   ne peut venir de la carte. */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) {
  header('Location: mjc-batiment.php'); exit;
}

$slug = preg_replace('/[^a-z0-9\-]/', '', $_POST['slug'] ?? '');
$vider = !empty($_POST['vider']);
$batiment = preg_replace('/[^0-9]/', '', $_POST['batiment'] ?? '');
if ($vider) $batiment = '';

if ($slug === '' || (!$vider && $batiment === '')) {
  header('Location: mjc-batiment.php' . ($slug ? '?slug=' . $slug : '')); exit;
}

$maisons = load_mjc();
$trouve = false;
foreach ($maisons as $i => $m) {
  if (($m['slug'] ?? '') === $slug) {
    $maisons[$i]['batiment'] = $batiment;
    $trouve = true;
    break;
  }
}
if ($trouve) save_mjc($maisons);

header('Location: mjc-batiment.php?slug=' . urlencode($slug) . '&ok=' . ($vider ? 'vide' : 'pose'));
exit;
