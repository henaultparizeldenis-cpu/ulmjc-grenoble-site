<?php
/* Fonctions communes du CMS ULMJC : données, formatage, images, sécurité.
   Basé sur mohamed-cms/site/inc/lib.php — on a GARDÉ le socle éprouvé
   (load/save JSON avec amorçage .default.json, slugify/unique_slug,
   optimize_image, sanitize_body, media_list/upload_path/media_disk_path,
   media_valid_src, clean_utf8, e()) et RETIRÉ tout le spécifique avocat
   (RDV/requests, iCal, matter_groups, hero/marbre, textes d'accueil, FAQ,
   pages légales, réglages d'apparence, catégories juridiques…).

   Nouveauté : le stockage des contenus est GÉNÉRIQUE via load_items($type) /
   save_items($type, $data), pour réutiliser facilement le même socle quand on
   ajoutera les Activités, Partenaires et le Chalet. Les Actualités (actus) sont
   implémentées PAR-DESSUS ces helpers génériques (voir plus bas). */

require_once __DIR__ . '/config.php';

/* ============================================================
   Stockage générique des contenus (un fichier JSON par type)
   ============================================================ */

/* Métadonnées d'un type de contenu (fichier, graine, libellé). */
function item_type_meta($type) {
  $types = isset($GLOBALS['ITEM_TYPES']) ? $GLOBALS['ITEM_TYPES'] : array();
  return isset($types[$type]) ? $types[$type] : null;
}

/* Charge la liste d'un type de contenu.
   Auto-amorçage : le fichier <type>.json n'est pas suivi par git (propre au
   serveur). S'il manque (1re installation ou juste après la bascule vers le
   dossier hors dépôt), on le recrée à partir de la graine versionnée
   <type>.default.json. Même mécanique que dans le CMS d'origine. */
function load_items($type) {
  $m = item_type_meta($type);
  if (!$m) return array();
  if (!is_file($m['file']) && !empty($m['seed']) && is_file($m['seed'])) {
    if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0775, true);
    @copy($m['seed'], $m['file']);
  }
  if (!is_file($m['file'])) return array();
  $data = json_decode(file_get_contents($m['file']), true);
  return is_array($data) ? $data : array();
}

/* Enregistre la liste d'un type de contenu (écriture atomique LOCK_EX).
   On ne renvoie/écrit JAMAIS 'false' : cela viderait le fichier (perte de tout). */
function save_items($type, $items) {
  $m = item_type_meta($type);
  if (!$m) return false;
  if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0775, true);
  $json = json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($json === false) return false;
  return file_put_contents($m['file'], $json, LOCK_EX) !== false;
}

/* Retrouve un élément par son slug dans un type donné. */
function find_item($type, $slug) {
  foreach (load_items($type) as $it) {
    if (isset($it['slug']) && $it['slug'] === $slug) return $it;
  }
  return null;
}

/* Éléments publiés d'un type, du plus récent au plus ancien. */
function published_items($type) {
  $list = array_filter(load_items($type), function ($it) { return !empty($it['published']); });
  usort($list, function ($x, $y) {
    $dx = ($x['date'] ?? '') . ($x['created'] ?? '');
    $dy = ($y['date'] ?? '') . ($y['created'] ?? '');
    return strcmp($dy, $dx); // décroissant
  });
  return array_values($list);
}

/* Garantit l'unicité du slug dans un type (ignore l'élément en cours d'édition). */
function unique_slug($type, $slug, $ignore = null) {
  $existing = array();
  foreach (load_items($type) as $it) {
    if ($ignore !== null && ($it['slug'] ?? '') === $ignore) continue;
    $existing[] = $it['slug'] ?? '';
  }
  $base = $slug; $i = 2;
  while (in_array($slug, $existing, true)) { $slug = $base . '-' . $i; $i++; }
  return $slug;
}

/* ============================================================
   Actualités (implémentées par-dessus les helpers génériques)
   ============================================================ */

function load_actus()            { return load_items('actus'); }
function save_actus($items)      { return save_items('actus', $items); }
function find_actu($slug)        { return find_item('actus', $slug); }
function published_actus()       { return published_items('actus'); }

/* Titre d'affichage d'une actu (repli si vide). */
function display_title($a) {
  return !empty($a['title']) ? $a['title'] : 'Sans titre';
}

/* Vignette de liste : couverture si présente, sinon ''. */
function list_thumb($a) { return !empty($a['cover']) ? $a['cover'] : ''; }
function has_thumb($a)  { return list_thumb($a) !== ''; }
function has_cover($a)  { return !empty($a['cover']); }

/* ============================================================
   Couverture : filtre couleur + effet de mouvement + taille
   ------------------------------------------------------------
   Porté de mohamed-cms/site/inc/lib.php (cover_filters / cover_style /
   effect_class / cover_aspect / cover_hero_ratio), avec la PALETTE adaptée à la
   charte ULMJC (pin / terre cuite). Ces helpers servent aussi bien aux actus
   qu'aux activités : les deux stockent 'filter'/'effect'/'cover_w'/'cover_align'
   à côté de leur image (champ 'cover' pour les actus, 'image' pour les activités,
   normalisé via cover_src()).
   ============================================================ */

/* Chemin de la couverture d'un élément, quel que soit le champ ('cover' ou 'image'). */
function cover_src($a) {
  if (!empty($a['cover'])) return $a['cover'];
  if (!empty($a['image'])) return $a['image'];
  return '';
}

/* Filtres couleur disponibles pour les couvertures (palette ULMJC : sobre).
   'layers' = calques background empilés AVANT l'image ; 'blend' = background-blend-mode ;
   'css'    = filtre CSS additionnel. Le duotone reprend pin (#1a3328) + terre cuite
   (#c4623a) de la charte, en voile discret. */
function cover_filters() {
  return array(
    'naturel' => array('label' => 'Couleur naturelle', 'layers' => "",                                                                                                 'blend' => 'normal',                'css' => ''),
    'nb'      => array('label' => 'Noir & blanc',       'layers' => "linear-gradient(#808080,#808080),",                                                                'blend' => 'color,normal',          'css' => ''),
    'sepia'   => array('label' => 'Sépia',              'layers' => "linear-gradient(150deg,rgba(120,82,42,.5),rgba(60,38,14,.55)),linear-gradient(#808080,#808080),",   'blend' => 'multiply,color,normal', 'css' => ''),
    'vif'     => array('label' => 'Couleur vive',       'layers' => "",                                                                                                 'blend' => 'normal',                'css' => 'saturate(1.4) contrast(1.05)'),
    'delave'  => array('label' => 'Délavé (doux)',      'layers' => "",                                                                                                 'blend' => 'normal',                'css' => 'saturate(.78) contrast(.93) brightness(1.05)'),
    'duotone' => array('label' => 'Duotone pin/terre',  'layers' => "linear-gradient(150deg,rgba(196,98,58,.42),rgba(26,51,40,.55)),linear-gradient(#808080,#808080),", 'blend' => 'multiply,color,normal', 'css' => ''),
  );
}

/* Clé de filtre validée (repli sur 'naturel'). */
function cover_filter_key($a) {
  $k = $a['filter'] ?? 'naturel';
  return array_key_exists($k, cover_filters()) ? $k : 'naturel';
}

/* Style inline d'une couverture selon le filtre choisi. $prefix : "../" en admin. */
function cover_style($a, $prefix = '') {
  $src = cover_src($a);
  if ($src === '') return '';
  $url = $prefix . $src;
  $filters = cover_filters();
  $f = $filters[cover_filter_key($a)];
  $style = "background-image:" . $f['layers'] . "url('" . e($url) . "');background-size:cover;background-position:center;background-blend-mode:" . $f['blend'] . ";";
  if (!empty($f['css'])) $style .= "filter:" . $f['css'] . ";";
  return $style;
}

/* Ratio (largeur/hauteur) de la couverture. Portrait → ratio naturel (pas de
   recadrage) ; paysage → 16:9 (bandeau ULMJC). */
function cover_aspect($a) {
  $src = cover_src($a);
  if ($src === '') return round(16 / 9, 4);
  $p = media_disk_path($src);
  if (is_file($p)) { $info = @getimagesize($p); if ($info && $info[1] > 0) return round($info[0] / $info[1], 4); }
  return round(16 / 9, 4);
}
function cover_hero_ratio($a) {
  $ar = cover_aspect($a);
  return $ar < 1 ? $ar : round(16 / 9, 4);
}

/* Largeur (%) de la couverture, bornée 40–100 (100 par défaut). */
function cover_width($a) {
  return isset($a['cover_w']) ? max(40, min(100, (int)$a['cover_w'])) : 100;
}
/* La couverture est-elle calée sur la largeur du titre ? */
function cover_align($a) { return !empty($a['cover_align']); }

/* Classe d'effet (animation) de la couverture. 'kenburns' = animation par défaut. */
function effect_class($a) {
  $e = $a['effect'] ?? 'kenburns';
  $map = array('fixe' => ' fx-fixe', 'zoom' => ' fx-zoom', 'pano' => ' fx-pano');
  return isset($map[$e]) ? $map[$e] : '';
}
/* Clé d'effet validée (repli sur 'kenburns'). */
function effect_key($a) {
  $e = $a['effect'] ?? 'kenburns';
  return in_array($e, array('kenburns', 'zoom', 'pano', 'fixe'), true) ? $e : 'kenburns';
}

/* ============================================================
   Tri par « ordre » (Activités, Partenaires) — croissant, puis titre/nom
   ============================================================ */

/* Éléments publiés d'un type, triés par le champ « ordre » (croissant).
   À défaut d'ordre identique, on départage par 'title' puis 'nom'. */
function published_ordered($type) {
  $list = array_filter(load_items($type), function ($it) { return !empty($it['published']); });
  usort($list, 'cmp_ordre');
  return array_values($list);
}

/* Comparateur réutilisable (liste admin ET public) : ordre croissant, repli alpha. */
function cmp_ordre($x, $y) {
  $ox = (int)($x['ordre'] ?? 0);
  $oy = (int)($y['ordre'] ?? 0);
  if ($ox !== $oy) return $ox <=> $oy;
  $lx = (string)($x['title'] ?? $x['nom'] ?? '');
  $ly = (string)($y['title'] ?? $y['nom'] ?? '');
  return strcmp($lx, $ly);
}

/* ============================================================
   Activités (par-dessus les helpers génériques, comme les actus)
   ============================================================ */

function load_activites()       { return load_items('activites'); }
function save_activites($items) { return save_items('activites', $items); }
function find_activite($slug)   { return find_item('activites', $slug); }
function published_activites()  { return published_ordered('activites'); }

/* ============================================================
   Partenaires (par-dessus les helpers génériques)
   ============================================================ */

function load_partenaires()       { return load_items('partenaires'); }
function save_partenaires($items) { return save_items('partenaires', $items); }
function published_partenaires()  { return published_ordered('partenaires'); }

/* ============================================================
   Photos du chalet : galerie par catégories (helpers DÉDIÉS)
   ------------------------------------------------------------
   Structure JSON = dictionnaire { "<categorie>": ["images/chalet/chalet-01.jpg", "uploads/xxx.jpg", …] }.
   Les 5 catégories sont FIXES (ordre d'affichage figé, libellés/icônes en dur dans les vues).
   Les chemins sont MIXTES : « images/… » (photos versionnées dans le dépôt) ou
   « uploads/… » (ajouts hors dépôt, servis par media.php). On ne force JAMAIS cette
   structure dans load_items() : d'où load_gallery()/save_gallery() séparés.
   ============================================================ */

/* Catégories du chalet : clé => libellé + icône (ordre = ordre d'affichage). */
function chalet_categories() {
  return array(
    'exterieur'     => array('label' => 'Extérieur',     'icon' => '🏞️'),
    'couchage'      => array('label' => 'Couchage',      'icon' => '🛏️'),
    'sanitaires'    => array('label' => 'Sanitaires',    'icon' => '🚿'),
    'cuisine'       => array('label' => 'Cuisine',       'icon' => '🍳'),
    'pieces-de-vie' => array('label' => 'Pièces de vie', 'icon' => '🏡'),
  );
}

/* Charge la galerie du chalet (dict catégorie→liste de chemins). Auto-amorçage
   depuis la graine versionnée, comme load_items(). Garantit TOUJOURS les 5 clés
   (dans le bon ordre) avec une liste, et ne conserve que des chemins valides. */
function load_gallery() {
  if (!is_file(GALLERY_FILE) && is_file(GALLERY_SEED)) {
    if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0775, true);
    @copy(GALLERY_SEED, GALLERY_FILE);
  }
  $raw = is_file(GALLERY_FILE) ? json_decode(file_get_contents(GALLERY_FILE), true) : array();
  if (!is_array($raw)) $raw = array();
  $out = array();
  foreach (chalet_categories() as $cat => $_meta) {
    $list = isset($raw[$cat]) && is_array($raw[$cat]) ? $raw[$cat] : array();
    $clean = array();
    foreach ($list as $src) {
      $v = gallery_valid_src($src);
      if ($v !== '' && !in_array($v, $clean, true)) $clean[] = $v;
    }
    $out[$cat] = $clean;
  }
  return $out;
}

/* Enregistre la galerie du chalet (écriture atomique). On ne conserve que les
   catégories connues et les chemins valides ; on n'écrit jamais 'false'. */
function save_gallery($gallery) {
  if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0775, true);
  $out = array();
  foreach (chalet_categories() as $cat => $_meta) {
    $list = isset($gallery[$cat]) && is_array($gallery[$cat]) ? $gallery[$cat] : array();
    $clean = array();
    foreach ($list as $src) {
      $v = gallery_valid_src($src);
      if ($v !== '' && !in_array($v, $clean, true)) $clean[] = $v;
    }
    $out[$cat] = $clean;
  }
  $json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($json === false) return false;
  return file_put_contents(GALLERY_FILE, $json, LOCK_EX) !== false;
}

/* Valide un chemin d'image de galerie : soit « images/… » (dépôt), soit « uploads/… »
   (hors dépôt). Contrairement à media_valid_src(), on accepte les sous-dossiers
   (images/chalet/…) et on n'exige PAS que le fichier existe sur le disque (les
   uploads vivent hors dépôt et ne sont pas forcément visibles en dev). */
function gallery_valid_src($src) {
  $src = trim((string) $src);
  if ($src !== '' && preg_match('#^(uploads|images)/[A-Za-z0-9._\-/]+\.(jpe?g|png|webp|gif)$#i', $src)
      && strpos($src, '..') === false) {
    return $src;
  }
  return '';
}

/* ============================================================
   Formatage
   ============================================================ */

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* Neutralise un octet mal encodé (copier-coller Word) qui ferait échouer json_encode. */
function clean_utf8($s) {
  $s = (string)$s;
  if ($s === '' || mb_check_encoding($s, 'UTF-8')) return $s;
  $conv = @mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
  return $conv !== false ? $conv : '';
}

function slugify($str) {
  $str = trim($str);
  $map = array(
    'à'=>'a','á'=>'a','â'=>'a','ä'=>'a','ã'=>'a','å'=>'a','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
    'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ñ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o','ö'=>'o','õ'=>'o',
    'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ý'=>'y','ÿ'=>'y',"'"=>' ','’'=>' '
  );
  $str = strtr(mb_strtolower($str, 'UTF-8'), $map);
  $str = preg_replace('/[^a-z0-9]+/u', '-', $str);
  $str = trim($str, '-');
  return $str !== '' ? $str : 'actu';
}

function fr_date($ymd) {
  $mois = array(1=>'janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre');
  $t = strtotime($ymd);
  if (!$t) return $ymd;
  return (int)date('j', $t) . ' ' . $mois[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function reading_time($html) {
  $words = str_word_count(strip_tags($html));
  $min = max(1, (int)round($words / 200));
  return $min . ' min de lecture';
}

/* ============================================================
   Images
   ============================================================ */

/* Chemin DISQUE d'un fichier importé à partir de son URL publique (« uploads/xxx »).
   Les uploads peuvent vivre HORS du dépôt : on reconstruit donc toujours le chemin
   réel depuis UPLOAD_DIR. Renvoie '' si l'URL n'est pas un upload. */
function upload_path($url) {
  $url = (string) $url;
  if (strpos($url, UPLOAD_URL . '/') !== 0) return '';
  return UPLOAD_DIR . '/' . basename($url);
}

/* Chemin DISQUE d'un média (importé uploads/, hors dépôt ; ou fourni images/, dans le dépôt). */
function media_disk_path($src) {
  $src = (string) $src;
  if (strpos($src, UPLOAD_URL . '/') === 0) return UPLOAD_DIR . '/' . basename($src);
  return BASE_DIR . '/' . $src;
}

/* Redimensionne et ré-encode une image en JPEG optimisé (GD). */
function optimize_image($srcPath, $destPath, $maxW = IMG_MAX_W, $quality = IMG_QUALITY) {
  if (!function_exists('imagecreatetruecolor')) return copy($srcPath, $destPath);
  $info = @getimagesize($srcPath);
  if (!$info) return false;
  switch ($info[2]) {
    case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($srcPath); break;
    case IMAGETYPE_PNG:  $src = @imagecreatefrompng($srcPath); break;
    case IMAGETYPE_WEBP: $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : false; break;
    case IMAGETYPE_GIF:  $src = @imagecreatefromgif($srcPath); break;
    default: $src = false;
  }
  if (!$src) return false;
  $w = imagesx($src); $h = imagesy($src);
  $nw = $w; $nh = $h;
  if ($w > $maxW) { $nw = $maxW; $nh = (int)round($h * $maxW / $w); }
  $dst = imagecreatetruecolor($nw, $nh);
  $white = imagecolorallocate($dst, 255, 255, 255);
  imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
  imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
  $ok = imagejpeg($dst, $destPath, $quality);
  imagedestroy($src); imagedestroy($dst);
  return $ok;
}

/* ============================================================
   Médiathèque : images réutilisables (uploads/ importées + images/ fournies)
   ============================================================ */

function media_list() {
  $items = array();
  if (is_dir(UPLOAD_DIR)) {
    foreach (glob(UPLOAD_DIR . '/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) as $f) {
      $items[] = array('src' => UPLOAD_URL . '/' . basename($f), 'name' => basename($f), 'del' => true, 'mtime' => (int) @filemtime($f));
    }
  }
  if (is_dir(BASE_DIR . '/images')) {
    foreach (glob(BASE_DIR . '/images/*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $f) {
      $items[] = array('src' => 'images/' . basename($f), 'name' => basename($f), 'del' => false, 'mtime' => (int) @filemtime($f));
    }
  }
  usort($items, function ($a, $b) { return $b['mtime'] - $a['mtime']; });
  return $items;
}

/* Valide un chemin d'image de la médiathèque (uploads/ ou images/, fichier existant). */
function media_valid_src($src) {
  $src = trim((string) $src);
  if ($src !== '' && preg_match('#^(uploads|images)/[A-Za-z0-9._\-]+\.(jpe?g|png|webp|gif)$#i', $src) && is_file(media_disk_path($src))) return $src;
  return '';
}

/* ============================================================
   Nettoyage du corps d'actu (éditeur visuel) — liste blanche DOMDocument
   ============================================================ */

/* Normalise une couleur CSS (#rgb, #rrggbb, rgb(r,g,b)) en #rrggbb minuscule, ou null. */
function _css_hex($v) {
  $v = strtolower(trim((string) $v));
  if (preg_match('/^#([0-9a-f]{6})$/', $v, $m)) return '#' . $m[1];
  if (preg_match('/^#([0-9a-f]{3})$/', $v, $m)) { $c = $m[1]; return '#' . $c[0].$c[0].$c[1].$c[1].$c[2].$c[2]; }
  if (preg_match('/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $v, $m)) return sprintf('#%02x%02x%02x', (int)$m[1], (int)$m[2], (int)$m[3]);
  return null;
}

/* Ne garde qu'une liste blanche de balises et d'attributs (anti-XSS).
   Adapté du CMS d'origine : palette et polices alignées sur la charte ULMJC. */
function sanitize_body($html, $allowed = null) {
  $html = trim((string)$html);
  if ($html === '') return '';
  if ($allowed === null) $allowed = array('p','h2','h3','strong','em','b','i','u','blockquote','ul','ol','li','br','a','img','figure','figcaption','span');
  $drop = array('script','style','iframe','object','embed','form','input','textarea','select','svg','link','meta','noscript','button'); // supprimés avec leur contenu

  $dom = new DOMDocument('1.0', 'UTF-8');
  libxml_use_internal_errors(true);
  $dom->loadHTML('<?xml encoding="UTF-8"?><div id="__root">' . $html . '</div>',
                 LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
  libxml_clear_errors();

  $root = $dom->getElementById('__root');
  if (!$root) return '';

  // Palette + polices autorisées pour la couleur/police du texte (charte ULMJC).
  $edPalette = array('#2a2926', '#6b6660', '#1a3328', '#2d4a3d', '#c4623a', '#a04e2c'); // ink, ink-soft, pine, pine-soft, terra, terra-dark
  $edFonts   = array('lora' => 'Lora', 'inter' => 'Inter');
  $clean = function ($node) use (&$clean, $allowed, $drop, $dom, $edPalette, $edFonts) {
    $children = iterator_to_array($node->childNodes);
    foreach ($children as $child) {
      if ($child->nodeType === XML_ELEMENT_NODE) {
        $tag = strtolower($child->nodeName);
        if (in_array($tag, $drop, true)) {
          $node->removeChild($child);
        } elseif (!in_array($tag, $allowed, true)) {
          // balise non autorisée : on remonte ses enfants (le texte) puis on la supprime
          $clean($child);
          while ($child->firstChild) { $node->insertBefore($child->firstChild, $child); }
          $node->removeChild($child);
        } else {
          if ($child->hasAttributes()) {
            $attrs = iterator_to_array($child->attributes);
            foreach ($attrs as $attr) {
              $an = strtolower($attr->name);
              $keep = ($tag === 'a' && $an === 'href')
                   || ($tag === 'img' && ($an === 'src' || $an === 'alt'))
                   || ($tag === 'figure' && in_array($an, array('class','style'), true))
                   || ($tag === 'span' && in_array($an, array('class','style'), true))
                   || (in_array($tag, array('p','h2','h3','blockquote','li'), true) && $an === 'class');
              if (!$keep) $child->removeAttribute($attr->name);
            }
          }
          if (in_array($tag, array('p','h2','h3','blockquote','li'), true)) {
            // alignement : on ne garde que ces classes-là sur les blocs
            $okCls = array('al-center','al-right','just');
            $kept  = array_values(array_intersect(preg_split('/\s+/', trim($child->getAttribute('class'))), $okCls));
            if ($kept) $child->setAttribute('class', implode(' ', $kept)); else $child->removeAttribute('class');
          }
          if ($tag === 'a') {
            // schémas autorisés uniquement (rejette javascript:, data:, vbscript:, file:…)
            $href = trim($child->getAttribute('href'));
            $ok = ($href !== '');
            if ($ok && preg_match('#^([a-z][a-z0-9+.\-]*):#i', $href, $m)) {
              $ok = in_array(strtolower($m[1]), array('http', 'https', 'mailto', 'tel'), true);
            }
            if (!$ok) $child->setAttribute('href', '#');
            $child->setAttribute('rel', 'noopener');
          }
          if ($tag === 'img') {
            // src limité aux images locales (uploads/ ou images/ fournies) ; sinon on retire l'image
            $src = $child->getAttribute('src');
            if (!preg_match('#^(uploads|images)/[A-Za-z0-9._\-/]+$#', $src)) { $node->removeChild($child); continue; }
            if ($child->getAttribute('alt') === '') $child->setAttribute('alt', '');
            $child->setAttribute('loading', 'lazy');
            $child->setAttribute('decoding', 'async');
          }
          if ($tag === 'figure') {
            // classe limitée à notre bloc image
            $child->setAttribute('class', 'a-img');
            $child->removeAttribute('style');
          }
          if ($tag === 'span') {
            // style en ligne : on ne garde QUE couleur (palette) et police (autorisées)
            $child->removeAttribute('class');
            $style = $child->getAttribute('style');
            if ($style !== '') {
              $keepDecl = array();
              foreach (explode(';', $style) as $decl) {
                $kv = explode(':', $decl, 2);
                if (count($kv) < 2) continue;
                $prop = strtolower(trim($kv[0])); $val = trim($kv[1]);
                if ($prop === 'color') {
                  $hex = _css_hex($val);
                  if ($hex !== null && in_array($hex, $edPalette, true)) $keepDecl[] = 'color:' . $hex;
                } elseif ($prop === 'font-family') {
                  $fam = strtolower(trim(explode(',', $val)[0], " \"'"));
                  if (isset($edFonts[$fam])) $keepDecl[] = "font-family:'" . $edFonts[$fam] . "'";
                }
              }
              if ($keepDecl) $child->setAttribute('style', implode(';', $keepDecl));
              else $child->removeAttribute('style');
            }
          }
          $clean($child);
        }
      } elseif ($child->nodeType === XML_COMMENT_NODE) {
        $node->removeChild($child);
      }
    }
  };
  $clean($root);

  // retire les blocs image sans aucune image
  foreach (iterator_to_array($root->getElementsByTagName('figure')) as $fig) {
    if ($fig->getElementsByTagName('img')->length === 0 && $fig->parentNode) {
      $fig->parentNode->removeChild($fig);
    }
  }

  $out = '';
  foreach ($root->childNodes as $c) $out .= $dom->saveHTML($c);
  // nettoie les paragraphes / figures vides éventuels
  $out = preg_replace('#<p>(\s|&nbsp;|<br\s*/?>)*</p>#i', '', $out);
  $out = preg_replace('#<figure[^>]*>\s*</figure>#i', '', $out);
  return trim($out);
}
