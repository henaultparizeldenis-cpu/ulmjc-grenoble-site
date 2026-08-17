<?php
/* Déconnexion. Basé sur mohamed-cms/site/admin/logout.php. */
require_once __DIR__ . '/auth.php';
/* Révoque aussi le « se souvenir de moi » de CET appareil, sinon la
   déconnexion serait illusoire : la page suivante reconnecterait aussitôt.
   Les autres appareils mémorisés restent valides. */
remember_forget();
$_SESSION = array();
if (ini_get('session.use_cookies')) {
  $p = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: index.php');
exit;
