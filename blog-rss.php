<?php
/* Flux RSS 2.0 du blog ULMJC. Un <item> par billet publié (30 plus récents),
   liens absolus vers billet.php?slug=…. Aucune écriture disque. */
require_once __DIR__ . '/inc/lib.php';

/* Base absolue du site (sert à construire les liens des items). En prod le site
   vit sur https://site.ulmjcgrenoble.org ; on la reconstruit toutefois depuis la
   requête pour rester correct en dev / preprod. */
function rss_base_url() {
  $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $scheme = $https ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'site.ulmjcgrenoble.org';
  // Dossier de la requête (permet un sous-dossier de staging), sans le fichier.
  $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
  return $scheme . '://' . $host . $dir . '/';
}

$base = rss_base_url();

/* Description RSS d'un billet : accroche, sinon chapô, sinon début du corps. */
function rss_item_desc($b) {
  $txt = trim((string)($b['excerpt'] ?? ''));
  if ($txt === '') $txt = trim((string)($b['chapo'] ?? ''));
  if ($txt === '') $txt = trim(preg_replace('/\s+/', ' ', strip_tags((string)($b['body'] ?? ''))));
  if (mb_strlen($txt) > 500) $txt = mb_substr($txt, 0, 497) . '…';
  return $txt;
}

/* Date RFC-822 (format exigé par RSS) à partir d'une date YYYY-MM-DD (+ created). */
function rss_pubdate($b) {
  $src = trim((string)($b['date'] ?? ''));
  $t = $src !== '' ? strtotime($src) : false;
  if (!$t && !empty($b['created'])) $t = strtotime($b['created']);
  if (!$t) $t = time();
  return date(DATE_RSS, $t);
}

$billets = published_blog();
$billets = array_slice($billets, 0, 30);

$now = date(DATE_RSS);

header('Content-Type: application/rss+xml; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>Blog — ULMJC Grenoble</title>
    <link><?= e($base . 'blog.php') ?></link>
    <atom:link href="<?= e($base . 'blog-rss.php') ?>" rel="self" type="application/rss+xml" />
    <description>Le blog de l'Union Locale des MJC de Grenoble : éducation populaire, sorties &amp; séjours, vie de l'association, portraits.</description>
    <language>fr-FR</language>
    <lastBuildDate><?= e($now) ?></lastBuildDate>
    <generator>CMS ULMJC</generator>
<?php foreach ($billets as $b):
  $link   = $base . 'billet.php?slug=' . rawurlencode($b['slug'] ?? '');
  $title  = display_title($b);
  $desc   = rss_item_desc($b);
  $cat    = blog_category_label(blog_category_key($b));
  $author = blog_author($b);
?>
    <item>
      <title><?= e($title) ?></title>
      <link><?= e($link) ?></link>
      <guid isPermaLink="true"><?= e($link) ?></guid>
      <pubDate><?= e(rss_pubdate($b)) ?></pubDate>
      <?php if ($cat !== ''): ?><category><?= e($cat) ?></category>
      <?php endif; ?><?php if ($author !== ''): ?><dc:creator xmlns:dc="http://purl.org/dc/elements/1.1/"><?= e($author) ?></dc:creator>
      <?php endif; ?><description><?= e($desc) ?></description>
    </item>
<?php endforeach; ?>
  </channel>
</rss>
