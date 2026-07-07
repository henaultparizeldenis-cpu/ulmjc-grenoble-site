<?php
/* Page publique : détail d'une actualité (?slug=). Style ULMJC. */
require_once __DIR__ . '/inc/lib.php';

$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']) : '';
$a = $slug ? find_actu($slug) : null;

if (!$a || empty($a['published'])) {
  http_response_code(404);
  $page_title  = 'Actualité introuvable — ULMJC Grenoble';
  $page_active = 'actus';
  require __DIR__ . '/inc/head.php';
  echo '<div class="page-header"><div class="container"><span class="section-eyebrow">Erreur 404</span>'
     . '<h1>Cette actualité est introuvable</h1>'
     . '<p class="lede">L\'article que vous cherchez n\'existe pas ou n\'est plus disponible.</p></div></div>'
     . '<section><div class="container center"><a href="actus.php" class="btn">Voir toutes les actualités</a></div></section>';
  require __DIR__ . '/inc/foot.php';
  exit;
}

$page_title  = display_title($a) . ' — ULMJC Grenoble';
$page_desc   = $a['excerpt'] ?? $page_title;
$page_active = 'actus';
require __DIR__ . '/inc/head.php';

// Autres actualités (2 récentes, hors celle-ci)
$related = array_filter(published_actus(), function ($x) use ($a) { return $x['slug'] !== $a['slug']; });
$related = array_slice(array_values($related), 0, 3);
?>
<style>
.actu-article-head{padding:3.5rem 0 0;}
.actu-back{display:inline-block;font-size:.9rem;color:var(--terra-dark);margin-bottom:1.2rem;border:none;}
.actu-article-meta{font-size:.85rem;color:var(--ink-soft);margin-top:.4rem;}
.actu-hero{max-width:960px;margin:2.5rem auto 0;border-radius:var(--radius);overflow:hidden;aspect-ratio:16/9;background:var(--bg-soft);}
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
/* Carte d'actu (partagée avec actus.php via inc/card.php) */
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
</style>

<div class="page-header actu-article-head">
  <div class="container">
    <a href="actus.php" class="actu-back">← Retour aux actualités</a>
    <span class="section-eyebrow">Actualité</span>
    <h1><?= e(display_title($a)) ?></h1>
    <div class="actu-article-meta"><?= fr_date($a['date'] ?? '') ?> · <?= reading_time($a['body'] ?? '') ?></div>
  </div>
</div>

<section>
  <div class="container">
    <?php if (has_cover($a)): ?>
    <div class="actu-hero reveal">
      <img src="<?= e($a['cover']) ?>" alt="<?= e(display_title($a)) ?>">
    </div>
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
      <h2>D'autres actualités</h2>
    </div>
    <div class="actu-related-grid reveal-stagger">
      <?php foreach ($related as $a): require __DIR__ . '/inc/card.php'; endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/inc/foot.php'; ?>
