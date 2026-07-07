<?php
/* Page publique : liste des billets de blog publiés. Basé sur actus.php.
   En plus : filtre par catégorie côté client (puces → data-cat, comme
   mohamed blog.php) et un lien vers le flux RSS. Réutilise inc/card-blog.php
   (qui montre catégorie + auteur). */
require_once __DIR__ . '/inc/lib.php';

$page_title  = 'Blog — ULMJC Grenoble';
$page_desc   = "Le blog de l'Union Locale des MJC de Grenoble : éducation populaire, sorties & séjours, vie de l'association, portraits.";
$page_active = 'blog';
$billets = published_blog();

// Catégories présentes parmi les billets publiés (ordre = ordre de blog_categories()).
$present = array();
foreach ($billets as $b) {
  $k = blog_category_key($b);
  if ($k !== '') $present[$k] = true;
}
$cats = array();
foreach (blog_categories() as $k => $label) {
  if (isset($present[$k])) $cats[$k] = $label;
}

require __DIR__ . '/inc/head.php';
?>
<link rel="alternate" type="application/rss+xml" title="Blog — ULMJC Grenoble" href="blog-rss.php">
<style>
/* Styles propres au blog (le reste vient de css/style.css et reprend .actu-card) */
.actu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem;margin-top:2.5rem;}
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
.actu-empty{text-align:center;max-width:580px;margin:0 auto;}
.actu-empty .icon{font-size:3rem;margin-bottom:1rem;}
/* Barre d'outils : filtre par catégorie + lien RSS */
.blog-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:1rem;margin-top:2rem;justify-content:space-between;}
.blog-tags{display:flex;flex-wrap:wrap;gap:.5rem;}
.blog-tag{font-family:'Inter',sans-serif;font-size:.85rem;color:var(--pine);background:var(--bg-soft);border:1px solid var(--taupe,var(--line));border-radius:20px;padding:.35rem .9rem;cursor:pointer;transition:background .2s,color .2s,border-color .2s;}
.blog-tag:hover{border-color:var(--pine-soft);}
.blog-tag.is-active{background:var(--pine);color:#fff;border-color:var(--pine);}
.blog-rss{display:inline-flex;align-items:center;gap:.4rem;font-size:.85rem;color:var(--terra-dark);border:none;white-space:nowrap;}
.blog-rss svg{flex:none;}
.blog-noresult{text-align:center;color:var(--ink-soft);margin-top:2rem;}
</style>

<div class="page-header">
  <div class="container">
    <span class="section-eyebrow">Blog</span>
    <h1>Le journal de l'asso.</h1>
    <p class="lede">Billets, retours d'expérience et regards sur l'éducation populaire, la vie de l'union et ses séjours. À lire au fil de l'eau.</p>
  </div>
</div>

<section>
  <div class="container">
    <?php if ($billets): ?>
      <div class="blog-toolbar">
        <?php if ($cats): ?>
        <div class="blog-tags" role="group" aria-label="Filtrer par thème">
          <button type="button" class="blog-tag is-active" data-cat="">Tous</button>
          <?php foreach ($cats as $k => $label): ?>
            <button type="button" class="blog-tag" data-cat="<?= e($k) ?>"><?= e($label) ?></button>
          <?php endforeach; ?>
        </div>
        <?php else: ?><span></span><?php endif; ?>
        <a class="blog-rss" href="blog-rss.php" aria-label="Flux RSS du blog">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="19" r="2.2"/><path d="M3 10.2a10.8 10.8 0 0 1 10.8 10.8h3.1A13.9 13.9 0 0 0 3 7.1z"/><path d="M3 3.5A17.5 17.5 0 0 1 20.5 21h3.1A20.6 20.6 0 0 0 3 .4z"/></svg>
          Flux RSS
        </a>
      </div>
      <div class="actu-grid reveal-stagger" id="blogList">
        <?php foreach ($billets as $a): require __DIR__ . '/inc/card-blog.php'; endforeach; ?>
      </div>
      <p class="blog-noresult" id="blogNoResult" hidden>Aucun billet dans ce thème pour le moment.</p>
    <?php else: ?>
      <div class="prose reveal actu-empty">
        <div class="icon">📝</div>
        <h2>Le blog arrive bientôt.</h2>
        <p style="color:var(--ink-soft);font-size:1.05rem;">
          Les premiers billets seront publiés ici prochainement.
          Inscrivez-vous à la newsletter pour ne rien rater.
        </p>
        <p style="margin-top:2rem;">
          <a href="contact.php" class="btn btn-accent">Nous contacter</a>
          <a href="index.php" class="btn btn-ghost" style="margin-left:.6rem;">Retour à l'accueil</a>
        </p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($billets && $cats): ?>
<script>
/* Filtre par catégorie côté client (aucune dépendance : autonome sur cette page).
   Chaque carte porte data-cat ; les puces filtrent l'affichage. */
(function(){
  var tags = document.querySelectorAll('.blog-tag');
  var cards = document.querySelectorAll('#blogList [data-cat]');
  var noRes = document.getElementById('blogNoResult');
  if (!tags.length || !cards.length) return;
  function apply(cat){
    var shown = 0;
    Array.prototype.forEach.call(cards, function(c){
      var ok = !cat || c.getAttribute('data-cat') === cat;
      c.style.display = ok ? '' : 'none';
      if (ok) shown++;
    });
    if (noRes) noRes.hidden = shown !== 0;
  }
  Array.prototype.forEach.call(tags, function(btn){
    btn.addEventListener('click', function(){
      Array.prototype.forEach.call(tags, function(t){ t.classList.remove('is-active'); });
      btn.classList.add('is-active');
      apply(btn.getAttribute('data-cat') || '');
    });
  });
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/inc/foot.php'; ?>
