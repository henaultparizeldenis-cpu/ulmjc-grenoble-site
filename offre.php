<?php
/* Page publique : détail d'une offre d'emploi (?slug=). Calquée sur billet.php.
   Une offre en brouillon, à la corbeille (find_item l'ignore) ou dont la date
   limite de candidature est dépassée renvoie un 404 : elle reste en base mais
   n'est plus consultable, exactement comme elle disparaît de la liste. */
require_once __DIR__ . '/inc/lib.php';

$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']) : '';
$a = $slug ? find_emploi($slug) : null;

if (!$a || empty($a['published']) || emploi_expired($a)) {
  http_response_code(404);
  $page_title  = 'Offre introuvable | ULMJC Grenoble';
  $page_active = 'emplois';
  require __DIR__ . '/inc/head.php';
  echo '<div class="page-header"><div class="container"><span class="section-eyebrow">Erreur 404</span>'
     . '<h1>Cette offre est introuvable</h1>'
     . '<p class="lede">L\'offre que vous cherchez n\'existe pas, ou la date limite de candidature est passée.</p></div></div>'
     . '<section><div class="container center"><a href="emplois.php" class="btn">Voir toutes les offres</a></div></section>';
  require __DIR__ . '/inc/foot.php';
  exit;
}

$page_title  = display_title($a) . " | Offre d'emploi | ULMJC Grenoble";
$page_desc   = $a['excerpt'] ?? $page_title;
$page_active = 'emplois';
require __DIR__ . '/inc/head.php';

$contrat = emploi_contrat_label(emploi_contrat_key($a));
$temps   = trim((string)($a['temps'] ?? ''));
$lieu    = trim((string)($a['lieu'] ?? ''));
$limite  = emploi_deadline($a);
$contact = trim((string)($a['contact'] ?? ''));

/* Encart « Comment postuler » : le texte est échappé PUIS les adresses email
   repérées deviennent des liens mailto (le motif exclut guillemets et chevrons,
   il ne peut donc pas s'échapper de l'attribut). */
$contactHtml = nl2br(e($contact));
$contactHtml = preg_replace('/([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})/',
                            '<a href="mailto:$1">$1</a>', $contactHtml);

// Autres offres ouvertes (3 récentes, hors celle-ci)
$related = array_filter(published_emplois(), function ($x) use ($a) { return $x['slug'] !== $a['slug']; });
$related = array_slice(array_values($related), 0, 3);
?>
<style>
/* En-tête d'offre : même moule que le détail d'un billet (billet.php), sans
   bandeau photo, une offre n'a pas d'image. */
.actu-article-head{padding:3rem 0 0;background:transparent;border-bottom:none;text-align:center;}
.actu-article-head+section{padding-top:0;}
.actu-article-head .container>*{display:block;max-width:720px;margin-left:auto;margin-right:auto;}
.actu-back{display:block;max-width:720px;margin:0 auto 1.2rem;font-size:.9rem;color:var(--terra-dark);border:none;}
.actu-article-meta{font-size:.85rem;color:var(--ink-soft);margin-top:.4rem;}
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
/* Étiquettes contrat / temps / lieu, et carte d'offre (partagée avec emplois.php). */
.emploi-tags{display:flex;flex-wrap:wrap;gap:.4rem;justify-content:center;margin:1rem 0 0;}
.emploi-tag{display:inline-block;font-size:.8rem;font-weight:500;letter-spacing:.04em;color:var(--terra-dark);background:var(--bg-soft);border:1px solid var(--taupe);border-radius:12px;padding:.2rem .7rem;}
.emploi-limit{font-size:.85rem;color:var(--ink-soft);font-style:italic;margin:.6rem 0 .2rem;}
.emploi-apply{max-width:720px;margin:1.5rem auto 0;}
.emploi-apply h2{margin-top:0;font-size:1.25rem;}
/* Lien vers la fiche de poste PDF : posé sous le corps, avant l'encart de candidature. */
.emploi-fiche{max-width:720px;margin:2rem auto 0;}
.emploi-fiche .btn{display:inline-block;}
.actu-card{padding:0;overflow:hidden;display:flex;flex-direction:column;}
.actu-card-body{padding:1.6rem;display:flex;flex-direction:column;flex:1;}
.actu-card-meta{font-size:.78rem;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:var(--terra-dark);margin-bottom:.6rem;}
.actu-card h3{margin-bottom:.5rem;}
.actu-card h3 a{color:var(--pine);border:none;}
.actu-card h3 a:hover{color:var(--terra-dark);border:none;}
.actu-card p{color:var(--ink-soft);font-size:.95rem;flex:1;}
.actu-related-grid .emploi-tags{justify-content:flex-start;margin:.2rem 0 .9rem;}
.actu-related-grid .emploi-tag{font-size:.74rem;padding:.15rem .6rem;}
</style>

<div class="page-header actu-article-head">
  <div class="container">
    <a href="emplois.php" class="actu-back">← Retour aux offres</a>
    <span class="section-eyebrow">Offre d'emploi<?= $contrat !== '' ? ' · ' . e($contrat) : '' ?></span>
    <h1><?= e(display_title($a)) ?></h1>
    <div class="actu-article-meta">
      Publiée le <?= fr_date($a['date'] ?? '') ?>
      <?php if ($limite !== ''): ?> · Candidatures jusqu'au <?= fr_date($limite) ?><?php endif; ?>
    </div>
    <?php if ($contrat !== '' || $temps !== '' || $lieu !== ''): ?>
    <div class="emploi-tags">
      <?php if ($contrat !== ''): ?><span class="emploi-tag">📄 <?= e($contrat) ?></span><?php endif; ?>
      <?php if ($temps !== ''): ?><span class="emploi-tag">🕒 <?= e($temps) ?></span><?php endif; ?>
      <?php if ($lieu !== ''): ?><span class="emploi-tag">📍 <?= e($lieu) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<section>
  <div class="container">
    <div class="actu-content">
      <?php if (!empty($a['excerpt'])): ?>
        <p class="actu-chapo reveal"><?= e($a['excerpt']) ?></p>
      <?php endif; ?>
      <div class="actu-body reveal"><?= $a['body'] ?? '' ?></div>

      <?php
      /* Fiche de poste complète en PDF, si elle a été jointe. Le chemin est
         revalidé à l'affichage : un fichier supprimé du disque ne laisse pas
         un lien mort. */
      $fiche = doc_valid_src($a['fiche'] ?? '');
      if ($fiche !== ''):
        $poids = doc_filesize_label($fiche);
      ?>
        <p class="emploi-fiche reveal">
          <a class="btn btn-ghost" href="<?= e($fiche) ?>" target="_blank" rel="noopener">
            Télécharger la fiche de poste complète (PDF<?= $poids !== '' ? ', ' . e($poids) : '' ?>)
          </a>
        </p>
      <?php endif; ?>
    </div>

    <div class="prose-callout emploi-apply reveal">
      <h2>Comment postuler</h2>
      <?php if ($contact !== ''): ?>
        <p><?= $contactHtml ?></p>
      <?php else: ?>
        <p>Écrivez-nous via la <a href="contact.php">page contact</a> en précisant l'intitulé du poste.</p>
      <?php endif; ?>
      <?php if ($limite !== ''): ?>
        <p><strong>Date limite de candidature :</strong> <?= fr_date($limite) ?>.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($related): ?>
<section style="background:var(--bg-soft);">
  <div class="container actu-related">
    <div class="center reveal">
      <span class="section-eyebrow">Également ouvert</span>
      <h2>D'autres offres</h2>
    </div>
    <div class="actu-related-grid reveal-stagger">
      <?php foreach ($related as $a): require __DIR__ . '/inc/card-emploi.php'; endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/inc/foot.php'; ?>
