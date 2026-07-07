<?php
/* « Supprimer » une activité = MISE À LA CORBEILLE (soft-delete, réversible).
   Anciennement un hard-delete ; voir admin/delete.php (actus) et corbeille.php. */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
  $slug = preg_replace('/[^a-z0-9\-]/', '', $_POST['slug'] ?? '');
  soft_delete_item('activites', $slug);
}
header('Location: activites.php?ok=trashed');
exit;
