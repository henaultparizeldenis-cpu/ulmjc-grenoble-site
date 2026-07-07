<?php
/* Page publique : fiches des Activités publiées. Remplace activites.html.
   Reprend le header/footer/style du site ULMJC (via inc/head.php, inc/foot.php).
   Sur le modèle d'actus.php : seuls les éléments publiés, triés par « ordre ».
   La description est du HTML déjà nettoyé côté serveur (sanitize_body à l'enregistrement). */
require_once __DIR__ . '/inc/lib.php';

$page_title  = 'Activités — ULMJC Grenoble';
$page_desc   = "Les activités autour du chalet de l'Alpe du Grand Serre : ski, raquettes, randos, lacs, baignade, VTT, patrimoine local en Isère.";
$page_active = 'activites';
$activites   = published_activites();

require __DIR__ . '/inc/head.php';
?>
<style>
/* Styles propres aux fiches d'activité (le reste vient de css/style.css) */
.act-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.6rem;margin-top:2.5rem;}
.act-card{background:var(--bg-card);border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;display:flex;flex-direction:column;transition:transform .2s,box-shadow .2s,border-color .2s;}
.act-card:hover{transform:translateY(-3px);box-shadow:var(--shadow);border-color:var(--pine-soft);}
.act-card-cover{display:block;aspect-ratio:16/10;overflow:hidden;background:var(--bg-soft);}
.act-card-cover img{width:100%;height:100%;object-fit:cover;display:block;}
.act-card-body{padding:1.6rem;display:flex;flex-direction:column;flex:1;}
.act-card-body h3{color:var(--pine);margin-bottom:.5rem;}
.act-tags{display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.9rem;}
.act-tag{display:inline-block;font-size:.74rem;font-weight:500;letter-spacing:.04em;color:var(--terra-dark);background:var(--bg-soft);border:1px solid var(--taupe);border-radius:12px;padding:.15rem .6rem;}
.act-desc{color:var(--ink-soft);font-size:.95rem;}
.act-desc p{margin:0 0 .6rem;}
.act-desc p:last-child{margin-bottom:0;}
.act-empty{text-align:center;max-width:580px;margin:0 auto;}
.act-empty .icon{font-size:3rem;margin-bottom:1rem;}
</style>

<div class="page-header">
  <div class="container">
    <span class="section-eyebrow">Activités</span>
    <h1>Tout ce qu'on peut faire ici.</h1>
    <p class="lede">Le chalet est posé au cœur d'un terrain de jeu qui change à chaque saison. Voici un aperçu de ce qui s'offre à vous autour de l'Alpe du Grand Serre.</p>
  </div>
</div>

<section>
  <div class="container">
    <?php if ($activites): ?>
      <div class="act-grid reveal-stagger">
        <?php foreach ($activites as $ac):
          $title = $ac['title'] ?? '';
          $img   = media_valid_src($ac['image'] ?? '');
          $jour  = trim((string)($ac['jour'] ?? ''));
          $horaire = trim((string)($ac['horaire'] ?? ''));
          $public  = trim((string)($ac['public'] ?? ''));
          ?>
          <article class="act-card reveal">
            <?php if ($img !== ''): ?>
            <div class="act-card-cover"><img src="<?= e($img) ?>" alt="<?= e($title) ?>" loading="lazy"></div>
            <?php endif; ?>
            <div class="act-card-body">
              <h3><?= e($title) ?></h3>
              <?php if ($jour !== '' || $horaire !== '' || $public !== ''): ?>
              <div class="act-tags">
                <?php if ($jour !== ''): ?><span class="act-tag">📅 <?= e($jour) ?></span><?php endif; ?>
                <?php if ($horaire !== ''): ?><span class="act-tag">🕒 <?= e($horaire) ?></span><?php endif; ?>
                <?php if ($public !== ''): ?><span class="act-tag">👥 <?= e($public) ?></span><?php endif; ?>
              </div>
              <?php endif; ?>
              <div class="act-desc"><?= $ac['description'] ?? '' ?></div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="prose reveal act-empty">
        <div class="icon">🏔️</div>
        <h2>Page en construction.</h2>
        <p style="color:var(--ink-soft);font-size:1.05rem;">
          Les activités autour du chalet seront présentées ici prochainement.
        </p>
        <p style="margin-top:2rem;">
          <a href="chalet.php" class="btn btn-accent">Voir le chalet</a>
          <a href="contact.html" class="btn btn-ghost" style="margin-left:.6rem;">Nous contacter</a>
        </p>
      </div>
    <?php endif; ?>
  </div>
</section>

<section style="background: var(--bg-soft);">
  <div class="container center reveal">
    <h2>Envie de venir&nbsp;?</h2>
    <p style="max-width: 580px; margin: 0 auto 2rem; color: var(--ink-soft);">
      Que ce soit pour un week-end, une semaine, un camp ou un séminaire associatif — le chalet est ouvert toute l'année.
      Contactez-nous pour vérifier les disponibilités.
    </p>
    <a href="contact.html" class="btn">Nous écrire</a>
    <a href="chalet.php" class="btn btn-ghost" style="margin-left: 0.75rem;">Voir Le Chalet</a>
  </div>
</section>

<?php require __DIR__ . '/inc/foot.php'; ?>
