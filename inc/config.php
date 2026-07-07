<?php
/* Configuration du CMS ULMJC (pilote « Actualités »).
   Fichier central : chemins, réglages, mode production.
   Basé sur mohamed-cms/site/inc/config.php — adapté pour ULMJC :
   - dossier voisin « ulmjc-data » (hors dépôt) au lieu de « mohamed-data » ;
   - DEV_MODE=false (production) ;
   - spécifique avocat retiré ;
   - fichiers de données génériques (voir load_items/save_items dans lib.php). */

date_default_timezone_set('Europe/Paris');

/* --- Chemins (système de fichiers) --- */
define('BASE_DIR', dirname(__DIR__));            // dossier racine déployé (.../public_html/site)

/* Données PERSISTANTES — elles doivent vivre HORS du dossier déployé, sinon chaque
   déploiement (qui remet le dépôt à l'identique) les efface. Ordre de choix :
   1) variable d'environnement ULMJC_DATA_DIR si définie ;
   2) sinon le dossier voisin « ulmjc-data » (public_html/ulmjc-data), utilisé
      UNIQUEMENT s'il existe (à créer une fois sur le serveur) ;
   3) sinon le dossier local site/data (dev, ou avant la bascule).
   → En créant public_html/ulmjc-data sur le serveur, la bascule se fait toute seule. */
$__envDir = getenv('ULMJC_DATA_DIR');
$__extDir = dirname(BASE_DIR) . '/ulmjc-data';   // public_html/ulmjc-data (voisin de public_html/site)
if ($__envDir !== false && $__envDir !== '') { define('DATA_DIR', rtrim($__envDir, "/\\")); }
elseif (is_dir($__extDir))                    { define('DATA_DIR', $__extDir); }
else                                          { define('DATA_DIR', BASE_DIR . '/data'); }
if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0775, true);
if (is_dir(DATA_DIR) && !is_file(DATA_DIR . '/.htaccess')) @file_put_contents(DATA_DIR . '/.htaccess', "Require all denied\nDeny from all\n");

define('REPO_DATA', BASE_DIR . '/data');         // graines VERSIONNÉES (restent dans le dépôt)
define('ADMIN_FILE', DATA_DIR . '/admin.json');  // empreinte du mot de passe (bcrypt)

/* Types de contenu gérés par le CMS. Pour le PILOTE, seul « actus » est actif.
   Ajouter une entrée ici + une graine data/<type>.default.json suffit à étendre
   (activites, partenaires, chalet viendront plus tard). load_items()/save_items()
   dans lib.php sont génériques et s'appuient sur cette table. */
$GLOBALS['ITEM_TYPES'] = array(
  'actus' => array(
    'file'  => DATA_DIR  . '/actus.json',
    'seed'  => REPO_DATA . '/actus.default.json',
    'label' => 'Actualités',
  ),
);

/* Photos importées : mêmes contraintes que les données (survivre au déploiement),
   MAIS elles doivent rester AFFICHABLES sur le web. Solution :
   - serveur (données hors dépôt) → uploads hors dépôt aussi (ulmjc-data/uploads),
     servis par media.php via une réécriture .htaccess (voir .htaccess) ;
   - local/dev (données dans site/data) → on garde site/uploads, servi directement.
   Ainsi les chemins stockés (« uploads/xxx.jpg ») ne changent JAMAIS. */
if (DATA_DIR === BASE_DIR . '/data') { define('UPLOAD_DIR', BASE_DIR . '/uploads'); }
else                                 { define('UPLOAD_DIR', DATA_DIR . '/uploads'); }
if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);

/* --- URL (relatives, pour l'affichage public) --- */
define('UPLOAD_URL', 'uploads');                 // URL logique (voir media.php / .htaccess)

/* --- Identité --- */
define('SITE_NAME', 'ULMJC Grenoble');

/* --- Version des assets (cache-busting CSS/JS) : calculée automatiquement
   d'après la date de modification de la feuille de style du site. */
$__asset_files = array(
  BASE_DIR . '/css/style.css',
  BASE_DIR . '/js/main.js',
);
$__asset_v = 0;
foreach ($__asset_files as $__f) { $__m = @filemtime($__f); if ($__m && $__m > $__asset_v) $__asset_v = $__m; }
define('ASSET_V', $__asset_v ? (string)$__asset_v : 'cms1');

/* --- Réglages images --- */
define('IMG_MAX_W', 1200);   // largeur max des photos uploadées
define('IMG_QUALITY', 82);   // qualité JPEG

/* --- Mode développement ---
   true  : affiche les erreurs PHP (utile pendant la mise en place)
   false : masque les erreurs (PRODUCTION) */
define('DEV_MODE', false);
if (DEV_MODE) {
  error_reporting(E_ALL);
  ini_set('display_errors', '1');
} else {
  error_reporting(0);
  ini_set('display_errors', '0');
}
