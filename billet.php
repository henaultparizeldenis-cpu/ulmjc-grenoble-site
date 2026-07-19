<?php
/* Page publique : détail d'un billet de blog (?slug=). Basé sur actu.php.
   En plus : affichage de l'AUTEUR et de la CATÉGORIE (« Par X · Catégorie · date »). */
require_once __DIR__ . '/inc/lib.php';

$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']) : '';
$a = $slug ? find_blog($slug) : null;

if (!$a || empty($a['published'])) {
  http_response_code(404);
  $page_title  = 'Billet introuvable — ULMJC Grenoble';
  $page_active = 'blog';
  require __DIR__ . '/inc/head.php';
  echo '<div class="page-header"><div class="container"><span class="section-eyebrow">Erreur 404</span>'
     . '<h1>Ce billet est introuvable</h1>'
     . '<p class="lede">Le billet que vous cherchez n\'existe pas ou n\'est plus disponible.</p></div></div>'
     . '<section><div class="container center"><a href="blog.php" class="btn">Voir tout le blog</a></div></section>';
  require __DIR__ . '/inc/foot.php';
  exit;
}

$page_title  = display_title($a) . ' — ULMJC Grenoble';
$page_desc   = $a['excerpt'] ?? $page_title;
$page_active = 'blog';
require __DIR__ . '/inc/head.php';

$author   = blog_author($a);
$catLabel = blog_category_label(blog_category_key($a));

// Autres billets (3 récents, hors celui-ci)
$related = array_filter(published_blog(), function ($x) use ($a) { return $x['slug'] !== $a['slug']; });
$related = array_slice(array_values($related), 0, 3);
?>
<style>
/* En-tête d'article SANS bandeau : le titre respire sur le fond de page,
   juste au-dessus de la photo (pas de fond coloré ni de bordure « boîte »). */
.actu-article-head{padding:3rem 0 0;background:transparent;border-bottom:none;text-align:center;}
.actu-article-head+section{padding-top:0;}
/* En-tête (accroche, titre, méta) calé sur la MÊME colonne de lecture (720px)
   que la couverture et le corps → tout s'aligne au lieu d'un titre pleine largeur. */
.actu-article-head .container>*{display:block;max-width:720px;margin-left:auto;margin-right:auto;}
.actu-back{display:block;max-width:720px;margin:0 auto 1.2rem;font-size:.9rem;color:var(--terra-dark);border:none;}
.actu-article-meta{font-size:.85rem;color:var(--ink-soft);margin-top:.4rem;}
/* Couverture : rendue en background-image (filtre/effet/taille appliqués inline
   via cover_style()/effect_class() ; règles fx-* + animations dans css/style.css). */
.actu-hero{max-width:960px;margin:2.5rem auto 0;border-radius:var(--radius);overflow:hidden;aspect-ratio:3/2;background:var(--bg-soft);background-size:cover;background-position:center;}
.actu-hero img{width:100%;height:100%;object-fit:cover;display:block;}
.actu-content{max-width:720px;margin:0 auto;padding:2.5rem 0 1rem;}
.actu-chapo{font-size:1.25rem;line-height:1.6;color:var(--pine);font-family:'Lora',Georgia,serif;font-style:italic;margin-bottom:1.8rem;}
.actu-body h2{margin-top:2.4rem;}
.actu-body h3{margin-top:1.8rem;}
.actu-body ul{padding-left:1.4rem;}
.actu-body li{margin-bottom:.5rem;}
.actu-body img{max-width:100%;height:auto;border-radius:var(--radius-sm);margin:1.4rem 0;}
.actu-body figure{margin:1.6rem 0;}
.actu-body figure img{margin:0;}
.actu-body figcaption{font-size:.85rem;color:var(--ink-soft);margin-top:.5rem;text-align:center;}
.actu-body blockquote{border-left:4px solid var(--terra);background:var(--bg-soft);margin:1.6rem 0;padding:1rem 1.4rem;border-radius:var(--radius-sm);font-family:'Lora',Georgia,serif;font-size:1.1rem;color:var(--pine);}
.actu-body .al-center{text-align:center;}
.actu-body .al-right{text-align:right;}
.actu-related{margin-top:1rem;}
.actu-related-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.5rem;margin-top:2rem;}
/* Carte de billet (partagée avec blog.php via inc/card-blog.php) */
.actu-card{padding:0;overflow:hidden;display:flex;flex-direction:column;}
.actu-card-cover{display:block;border:none;aspect-ratio:16/10;overflow:hidden;background:var(--bg-soft);}
.actu-card-cover:hover{border:none;}
.actu-card-cover img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .4s ease;}
.actu-card:hover .actu-card-cover img{transform:scale(1.04);}
.actu-card-body{padding:1.6rem;display:flex;flex-direction:column;flex:1;}
.actu-card-meta{font-size:.78rem;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:var(--terra-dark);margin-bottom:.6rem;}
.actu-card h3{margin-bottom:.5rem;}
.actu-card h3 a{color:var(--pine);border:none;}
.actu-card h3 a:hover{color:var(--terra-dark);border:none;}
.actu-card p{color:var(--ink-soft);font-size:.95rem;flex:1;}
.blog-card-author{font-size:.85rem;color:var(--ink-soft);font-style:italic;margin:.4rem 0 .2rem;}
</style>

<div class="page-header actu-article-head">
  <div class="container">
    <a href="blog.php" class="actu-back">← Retour au blog</a>
    <span class="section-eyebrow">Blog<?= $catLabel !== '' ? ' · ' . e($catLabel) : '' ?></span>
    <h1><?= e(display_title($a)) ?></h1>
    <div class="actu-article-meta">
      <?php if ($author !== ''): ?>Par <?= e($author) ?> · <?php endif; ?>
      <?php if ($catLabel !== ''): ?><?= e($catLabel) ?> · <?php endif; ?>
      <?= fr_date($a['date'] ?? '') ?> · <?= reading_time($a['body'] ?? '') ?>
    </div>
  </div>
</div>

<section>
  <div class="container">
    <?php if (has_cover($a)):
      // Couverture avec filtre couleur + effet de mouvement + taille (comme les actus).
      $cw = cover_width($a); $alignTitle = cover_align($a);
      $heroMax = $alignTitle ? 720 : (int)round(960 * $cw / 100);
    ?>
    <div class="actu-hero reveal<?= effect_class($a) ?>" role="img" aria-label="<?= e(display_title($a)) ?>" style="<?= cover_style($a) ?>aspect-ratio:<?= cover_hero_ratio($a) ?>;max-width:<?= $heroMax ?>px;"></div>
    <?php endif; ?>

    <div class="actu-content">
      <?php if (!empty($a['chapo'])): ?>
        <p class="actu-chapo reveal"><?= e($a['chapo']) ?></p>
      <?php endif; ?>
      <div class="actu-body reveal"><?= $a['body'] ?? '' ?></div>
    </div>
  </div>
</section>

<?php if ($related): ?>
<section style="background:var(--bg-soft);">
  <div class="container actu-related">
    <div class="center reveal">
      <span class="section-eyebrow">À lire aussi</span>
      <h2>D'autres billets</h2>
    </div>
    <div class="actu-related-grid reveal-stagger">
      <?php foreach ($related as $a): require __DIR__ . '/inc/card-blog.php'; endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/inc/foot.php'; ?>
