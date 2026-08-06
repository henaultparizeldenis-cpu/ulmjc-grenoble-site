<?php
/* « Supprimer » une offre d'emploi = MISE À LA CORBEILLE (soft-delete, réversible).
   Calqué sur admin/billet-delete.php ; la restauration/purge se fait depuis
   admin/corbeille.php. */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
  $slug = preg_replace('/[^a-z0-9\-]/', '', $_POST['slug'] ?? '');
  soft_delete_item('emplois', $slug);
}
header('Location: emplois.php?ok=trashed');
exit;
