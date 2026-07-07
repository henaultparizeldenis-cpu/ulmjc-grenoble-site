<?php
/* Médiathèque : liste JSON des images réutilisables (uploads/ + images/).
   Basé sur mohamed-cms/site/admin/media-list.php. */
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');
if (!is_logged_in()) { http_response_code(403); echo json_encode(array('error' => 'Non connecté')); exit; }
echo json_encode(array('items' => media_list()));
