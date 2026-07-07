<?php
/* Suppression d'un billet de blog. Basé sur admin/delete.php (actus). */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
  $slug = preg_replace('/[^a-z0-9\-]/', '', $_POST['slug'] ?? '');
  $kept = array();
  foreach (load_blog() as $a) {
    if (($a['slug'] ?? '') === $slug) {
      $f = upload_path(isset($a['cover']) ? $a['cover'] : '');
      if ($f !== '' && is_file($f)) @unlink($f);
      continue; // on n'ajoute pas → supprimé
    }
    $kept[] = $a;
  }
  save_blog($kept);
}
header('Location: blog.php?ok=deleted');
exit;
