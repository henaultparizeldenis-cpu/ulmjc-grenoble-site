<?php
/* Médiathèque : suppression d'une image (seulement celles de uploads/).
   Basé sur mohamed-cms/site/admin/media-delete.php. */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');
if (!is_logged_in()) { http_response_code(403); echo json_encode(array('error' => 'Non connecté')); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) { http_response_code(403); echo json_encode(array('error' => 'Jeton invalide')); exit; }
$src = media_valid_src($_POST['src'] ?? '');
$p = upload_path($src);
if ($p === '') { echo json_encode(array('error' => 'Image non supprimable.')); exit; }
if (is_file($p)) @unlink($p);
echo json_encode(array('ok' => true));
