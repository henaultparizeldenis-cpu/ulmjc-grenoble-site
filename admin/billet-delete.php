<?php
/* « Supprimer » un billet de blog = MISE À LA CORBEILLE (soft-delete, réversible).
   Anciennement un hard-delete ; voir admin/delete.php (actus) et corbeille.php. */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
  $slug = preg_replace('/[^a-z0-9\-]/', '', $_POST['slug'] ?? '');
  soft_delete_item('blog', $slug);
}
header('Location: blog.php?ok=trashed');
exit;
