<?php
/* Page publique : grille des Partenaires publiés. Remplace partenariats.html.
   Reprend le header/footer/style du site ULMJC (via inc/head.php, inc/foot.php).
   Sur le modèle d'actus.php : seuls les éléments publiés, triés par « ordre ». */
require_once __DIR__ . '/inc/lib.php';

$page_title  = 'Partenaires — ULMJC Grenoble';
$page_desc   = "Les institutions, associations et structures partenaires de l'Union Locale des MJC de Grenoble.";
$page_active = 'partenaires';
$partenaires = published_partenaires();

require __DIR__ . '/inc/head.php';
?>
<style>
/* Styles propres aux partenaires (le reste vient de css/style.css) */
.part-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1.4rem;margin-top:2.5rem;}
.part-item{display:flex;align-items:center;justify-content:center;background:var(--bg-card);border:1px solid var(--line);border-radius:var(--radius);padding:1.6rem;min-height:140px;transition:transform .2s,box-shadow .2s,border-color .2s;}
.part-item:hover{transform:translateY(-3px);box-shadow:var(--shadow);border-color:var(--pine-soft);}
.part-item img{max-width:100%;max-height:96px;width:auto;height:auto;object-fit:contain;display:block;}
.part-item .part-name{font-family:'Lora',Georgia,serif;color:var(--pine);text-align:center;font-size:1.1rem;line-height:1.3;}
a.part-item{border:1px solid var(--line);}
a.part-item:hover{border-color:var(--pine-soft);}
.part-empty{text-align:center;max-width:580px;margin:0 auto;}
.part-empty .icon{font-size:3rem;margin-bottom:1rem;}
</style>

<div class="page-header">
  <div class="container">
    <span class="section-eyebrow">Notre écosystème</span>
    <h1>Partenariats.</h1>
    <p class="lede">L'Union Locale des MJC ne fait jamais cavalier seul. Voici les institutions, associations et structures avec qui nous collaborons au quotidien.</p>
  </div>
</div>

<section>
  <div class="container">
    <?php if ($partenaires): ?>
      <div class="part-grid reveal-stagger">
        <?php foreach ($partenaires as $p):
          $nom  = $p['nom'] ?? '';
          $logo = media_valid_src($p['logo'] ?? '');
          $url  = trim((string)($p['url'] ?? ''));
          $ext  = ($url !== '' && preg_match('#^https?://#i', $url));
          $tag  = $ext ? 'a' : 'div';
          ?>
          <<?= $tag ?> class="part-item"<?= $ext ? ' href="' . e($url) . '" target="_blank" rel="noopener"' : '' ?> title="<?= e($nom) ?>">
            <?php if ($logo !== ''): ?>
              <img src="<?= e($logo) ?>" alt="<?= e($nom) ?>" loading="lazy">
            <?php else: ?>
              <span class="part-name"><?= e($nom) ?></span>
            <?php endif; ?>
          </<?= $tag ?>>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="prose reveal part-empty">
        <div class="icon">🌱</div>
        <h2>Page en construction.</h2>
        <p style="color:var(--ink-soft);font-size:1.05rem;">
          Nous mettons à jour la liste de nos partenaires institutionnels, associatifs et financiers.
          En attendant, n'hésitez pas à nous contacter pour échanger sur un éventuel partenariat.
        </p>
        <p style="margin-top:2rem;">
          <a href="contact.html" class="btn btn-accent">Nous contacter</a>
          <a href="asso.html" class="btn btn-ghost" style="margin-left:.6rem;">Découvrir l'asso</a>
        </p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/inc/foot.php'; ?>
