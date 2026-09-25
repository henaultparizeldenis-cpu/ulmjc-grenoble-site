<?php
/* « Supprimer » une maison = MISE À LA CORBEILLE (soft-delete, réversible).
   Identifiant = « slug ». Même mécanique que les autres types : elle disparaît
   de la page Les MJC et de la carte, mais reste récupérable depuis la
   corbeille tant qu'on ne l'a pas vidée. */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
  $slug = preg_replace('/[^a-z0-9\-]/', '', $_POST['slug'] ?? '');
  soft_delete_item('mjc', $slug);
}
header('Location: mjc.php?ok=trashed');
exit;
