<?php
/* Page publique : liste des Actualités publiées. Remplace actus.html.
   Reprend le header/footer/style du site ULMJC (via inc/head.php, inc/foot.php)
   et affiche chaque actu publiée avec inc/card.php. */
require_once __DIR__ . '/inc/lib.php';

$page_title  = 'Actualités — ULMJC Grenoble';
$page_desc   = "Actualités de l'Union Locale des MJC de Grenoble : événements à venir, comptes rendus, communications du bureau.";
$page_active = 'actus';
$actus = published_actus();

require __DIR__ . '/inc/head.php';
?>
<style>
/* Styles propres aux actualités (le reste vient de css/style.css) */
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
.actu-empty{text-align:center;max-width:580px;margin:0 auto;}
.actu-empty .icon{font-size:3rem;margin-bottom:1rem;}
</style>

<div class="page-header">
  <div class="container">
    <span class="section-eyebrow">Actualités</span>
    <h1>Ce qui se passe à l'asso.</h1>
    <p class="lede">Événements à venir, comptes rendus de sortie, communications du bureau. Pour ne rien rater, inscrivez-vous à la newsletter.</p>
  </div>
</div>

<section>
  <div class="container">
    <?php if ($actus): ?>
      <div class="actu-grid reveal-stagger">
        <?php foreach ($actus as $a): require __DIR__ . '/inc/card.php'; endforeach; ?>
      </div>
    <?php else: ?>
      <div class="prose reveal actu-empty">
        <div class="icon">📰</div>
        <h2>Page en construction.</h2>
        <p style="color:var(--ink-soft);font-size:1.05rem;">
          Les actualités, événements et comptes-rendus seront publiés ici prochainement.
          Inscrivez-vous à la newsletter pour ne rien rater.
        </p>
        <p style="margin-top:2rem;">
          <a href="contact.html" class="btn btn-accent">Nous contacter</a>
          <a href="index.html" class="btn btn-ghost" style="margin-left:.6rem;">Retour à l'accueil</a>
        </p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/inc/foot.php'; ?>
