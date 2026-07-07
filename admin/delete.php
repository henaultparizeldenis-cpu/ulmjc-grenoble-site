<?php
/* « Supprimer » une actualité = MISE À LA CORBEILLE (soft-delete, réversible).
   Anciennement un hard-delete (basé sur mohamed-cms/site/admin/delete.php) ; on
   pose désormais deleted=true + deleted_at et l'élément part dans corbeille.php,
   d'où on peut le restaurer ou le supprimer définitivement (purge_item). */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
  $slug = preg_replace('/[^a-z0-9\-]/', '', $_POST['slug'] ?? '');
  soft_delete_item('actus', $slug);
}
header('Location: index.php?ok=trashed');
exit;
