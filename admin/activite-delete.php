<?php
/* Suppression d'une activité. Calqué sur admin/delete.php (actualités). */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
  $slug = preg_replace('/[^a-z0-9\-]/', '', $_POST['slug'] ?? '');
  $kept = array();
  foreach (load_activites() as $a) {
    if (($a['slug'] ?? '') === $slug) {
      $f = upload_path(isset($a['image']) ? $a['image'] : '');
      if ($f !== '' && is_file($f)) @unlink($f);
      continue; // on n'ajoute pas → supprimé
    }
    $kept[] = $a;
  }
  save_activites($kept);
}
header('Location: activites.php?ok=deleted');
exit;
