<?php
/* Actions de la corbeille : restaurer / supprimer définitivement / vider.
   Toutes les écritures sont protégées par CSRF (comme les endpoints delete.php).
   - action=restore : restore_item() → l'élément revient dans sa liste normale.
   - action=purge   : purge_item() → suppression DÉFINITIVE (hard-delete + fichiers).
   - action=empty   : purge tous les éléments en corbeille (« Vider la corbeille »).
   Le type et la clé (slug/id) sont validés contre la liste des types autorisés. */
require_once __DIR__ . '/auth.php';
require_login();

/* Types « liste » concernés par la corbeille (le chalet est une galerie, exclu). */
$ALLOWED_TYPES = array('actus', 'blog', 'activites', 'partenaires');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
  $action = $_POST['action'] ?? '';

  if ($action === 'empty') {
    // Vider la corbeille : purge de tous les éléments supprimés, tous types.
    foreach ($ALLOWED_TYPES as $type) {
      foreach (trashed_items($type) as $it) {
        $key = $it[item_key_field($type)] ?? null;
        if ($key !== null) purge_item($type, $key);
      }
    }
  } else {
    $type = $_POST['type'] ?? '';
    // La clé peut être un slug (a-z0-9-) ou un id partenaire (même charset toléré).
    $key  = preg_replace('/[^a-z0-9\-]/', '', $_POST['key'] ?? '');
    if (in_array($type, $ALLOWED_TYPES, true) && $key !== '') {
      if ($action === 'restore')    restore_item($type, $key);
      elseif ($action === 'purge')  purge_item($type, $key);
    }
  }
}

header('Location: corbeille.php');
exit;
