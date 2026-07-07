<?php
/* « Supprimer » un partenaire = MISE À LA CORBEILLE (soft-delete, réversible).
   Identifiant = « id » (pas « slug »). Anciennement un hard-delete ; voir
   admin/delete.php (actus) et corbeille.php. */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
  $id = preg_replace('/[^a-z0-9\-]/', '', $_POST['id'] ?? '');
  soft_delete_item('partenaires', $id);
}
header('Location: partenaires.php?ok=trashed');
exit;
