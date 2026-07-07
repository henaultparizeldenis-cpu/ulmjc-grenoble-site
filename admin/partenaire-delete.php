<?php
/* Suppression d'un partenaire. Calqué sur admin/delete.php, identifiant = « id ». */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
  $id = preg_replace('/[^a-z0-9\-]/', '', $_POST['id'] ?? '');
  $kept = array();
  foreach (load_partenaires() as $p) {
    if (($p['id'] ?? '') === $id) {
      $f = upload_path(isset($p['logo']) ? $p['logo'] : '');
      if ($f !== '' && is_file($f)) @unlink($f);
      continue; // supprimé
    }
    $kept[] = $p;
  }
  save_partenaires($kept);
}
header('Location: partenaires.php?ok=deleted');
exit;
