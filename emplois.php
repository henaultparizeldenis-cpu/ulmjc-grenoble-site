<?php
/* Page publique : liste des offres d'emploi publiées. Calquée sur blog.php /
   actus.php (mêmes classes .actu-* et .page-header — aucune charte nouvelle).
   published_emplois() ne renvoie QUE les offres publiées, hors corbeille, dont
   la date limite de candidature n'est pas dépassée. */
require_once __DIR__ . '/inc/lib.php';

$page_title  = "Offres d'emploi — ULMJC Grenoble";
$page_desc   = "Les offres d'emploi, de stage, d'apprentissage et de service civique de l'Union Locale des MJC de Grenoble.";
$page_active = 'emplois';
$offres = published_emplois();

require __DIR__ . '/inc/head.php';
?>
<style>
/* Styles propres aux offres d'emploi (le reste vient de css/style.css et reprend
   .actu-card, comme le blog et les actualités). */
.actu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem;margin-top:2.5rem;}
.actu-card{padding:0;overflow:hidden;display:flex;flex-direction:column;}
.actu-card-body{padding:1.6rem;display:flex;flex-direction:column;flex:1;}
.actu-card-meta{font-size:.78rem;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:var(--terra-dark);margin-bottom:.6rem;}
.actu-card h3{margin-bottom:.5rem;}
.actu-card h3 a{color:var(--pine);border:none;}
.actu-card h3 a:hover{color:var(--terra-dark);border:none;}
.actu-card p{color:var(--ink-soft);font-size:.95rem;flex:1;}
.emploi-tags{display:flex;flex-wrap:wrap;gap:.4rem;margin:.2rem 0 .9rem;}
.emploi-tag{display:inline-block;font-size:.74rem;font-weight:500;letter-spacing:.04em;color:var(--terra-dark);background:var(--bg-soft);border:1px solid var(--taupe);border-radius:12px;padding:.15rem .6rem;}
.emploi-limit{font-size:.85rem;color:var(--ink-soft);font-style:italic;margin:.6rem 0 .2rem;}
.actu-empty{text-align:center;max-width:580px;margin:0 auto;}
.actu-empty .icon{font-size:3rem;margin-bottom:1rem;}
</style>

<div class="page-header">
  <div class="container">
    <span class="section-eyebrow">Rejoindre l'équipe</span>
    <h1>Offres d'emploi.</h1>
    <p class="lede">Postes salariés, apprentissages, stages, services civiques et missions bénévoles au sein de l'union et de ses MJC.</p>
  </div>
</div>

<section>
  <div class="container">
    <?php if ($offres): ?>
      <div class="actu-grid reveal-stagger">
        <?php foreach ($offres as $a): require __DIR__ . '/inc/card-emploi.php'; endforeach; ?>
      </div>
    <?php else: ?>
      <div class="prose reveal actu-empty">
        <div class="icon">💼</div>
        <h2>Aucune offre en ce moment.</h2>
        <p style="color:var(--ink-soft);font-size:1.05rem;">
          Nous n'avons pas de poste à pourvoir pour l'instant — revenez bientôt, cette page est mise à jour
          dès qu'une offre s'ouvre. Vous pouvez aussi nous écrire : les candidatures spontanées et les
          propositions de bénévolat sont toujours les bienvenues.
        </p>
        <p style="margin-top:2rem;">
          <a href="contact.php" class="btn btn-accent">Nous écrire</a>
          <a href="asso.php" class="btn btn-ghost" style="margin-left:.6rem;">Découvrir l'association</a>
        </p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/inc/foot.php'; ?>
