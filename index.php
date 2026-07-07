<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="L'Union Locale des MJC de Grenoble fédère 7 maisons de quartier qui font vivre l'éducation populaire depuis 1961. Et un chalet à l'Alpe du Grand Serre.">
<title>ULMJC Grenoble — l'éducation populaire en mouvement</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="canonical" href="https://site.ulmjcgrenoble.org/">
<link rel="stylesheet" href="css/style.css?v=20260524-14">
<!-- Matomo Analytics - mode anonyme (sans cookies, IP anonymisee) -->
<script>
  var _paq = window._paq = window._paq || [];
  _paq.push(['disableCookies']);
  _paq.push(['setDoNotTrack', true]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u = "//stats.ulmjcgrenoble.org/matomo/";
    _paq.push(['setTrackerUrl', u + 'matomo.php']);
    _paq.push(['setSiteId', '1']);
    var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
    g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
  })();
</script>
<!-- End Matomo -->
<!-- Données structurées : identité de l'association pour Google -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://site.ulmjcgrenoble.org/#organization",
      "name": "Union Locale des MJC de Grenoble",
      "alternateName": ["ULMJC Grenoble", "ULMJC", "Union Locale des Maisons de la Jeunesse et de la Culture de Grenoble"],
      "url": "https://site.ulmjcgrenoble.org/",
      "foundingDate": "1961-09-27",
      "description": "Association loi 1901 d'éducation populaire qui fédère sept MJC et Maisons Pour Tous de Grenoble et gère un chalet à l'Alpe du Grand Serre.",
      "email": "ulmjc.gre@free.fr",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "6 rue Berthe de Boissieux",
        "postalCode": "38000",
        "addressLocality": "Grenoble",
        "addressCountry": "FR"
      }
    },
    {
      "@type": "WebSite",
      "@id": "https://site.ulmjcgrenoble.org/#website",
      "url": "https://site.ulmjcgrenoble.org/",
      "name": "ULMJC Grenoble",
      "inLanguage": "fr",
      "publisher": { "@id": "https://site.ulmjcgrenoble.org/#organization" }
    }
  ]
}
</script>
</head>
<body>

<!-- Splash d'intro : logo animé, affiché 1× par session, skipable -->
<div id="splash" class="splash" role="dialog" aria-label="Intro animée du logo ULMJC">
  <video id="splash-video" class="splash-video" playsinline muted preload="auto" aria-hidden="true">
    <source src="videos/intro.mp4" type="video/mp4">
  </video>
  <button type="button" class="splash-skip" aria-label="Passer l'intro">Passer&nbsp;›</button>
  <div class="splash-hint" aria-hidden="true">Cliquez pour entrer sur le site</div>
</div>

<?php $active = 'accueil'; include __DIR__ . '/inc/nav.php'; ?>

<section class="hero has-photo" data-weather-target>
  <div class="hero-bg-wrap" data-parallax>
    <video class="hero-bg" autoplay muted loop playsinline preload="metadata" poster="images/bastille.jpg" aria-hidden="true" data-loop-start="0" data-loop-end="11.2">
      <source src="videos/hero.mp4" type="video/mp4">
    </video>
  </div>
  <div class="hero-overlay"></div>
  <div class="hero-vignette"></div>
  <div class="weather-fx" aria-hidden="true"></div>
  <div class="container hero-content">
    <span class="section-eyebrow">Union Locale des MJC de Grenoble · depuis 1961</span>
    <h1>Sept maisons, un mouvement.</h1>
    <p class="lede">
      Depuis 1961, l'Union Locale des MJC de Grenoble fédère <strong>sept maisons de quartier</strong>
      qui font vivre l'éducation populaire&nbsp;: des lieux ouverts à tous pour apprendre, créer, débattre,
      faire ensemble. Et un chalet en montagne, à partager.
    </p>
    <div class="hero-actions">
      <a href="les-mjc.php" class="btn">Trouver une MJC</a>
      <a href="chalet.php" class="btn btn-ghost">Découvrir Le Chalet</a>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="center reveal" style="max-width: 720px; margin: 0 auto;">
      <span class="section-eyebrow">L'éducation populaire</span>
      <h2>Apprendre, créer, agir — ensemble.</h2>
      <p style="color: var(--ink-soft); font-size: 1.1rem; margin-top: 1rem;">
        Un mouvement né au XIX<sup>e</sup> siècle qui considère que chacun a sa place pour apprendre,
        transmettre et agir, en dehors des circuits scolaires et institutionnels. Pas de cours magistraux&nbsp;:
        des collectifs, des pratiques partagées, des espaces ouverts à tous, à tarifs solidaires.
      </p>
    </div>
    <div class="cards reveal-stagger">
      <div class="card">
        <div class="icon">🤝</div>
        <h3>Solidarité</h3>
        <p>Permettre l'accès au savoir, à la culture et aux loisirs pour toutes et tous, quels que soient les moyens.</p>
      </div>
      <div class="card">
        <div class="icon">🌱</div>
        <h3>Émancipation</h3>
        <p>Apprendre à penser, à choisir, à agir par soi-même. Donner à chacun les clés pour participer à la vie collective.</p>
      </div>
      <div class="card">
        <div class="icon">🔄</div>
        <h3>Coopération</h3>
        <p>Faire <em>ensemble</em> plutôt que recevoir. Échanger les savoirs, partager les pratiques, construire en collectif.</p>
      </div>
      <div class="card">
        <div class="icon">🏡</div>
        <h3>Proximité</h3>
        <p>Au cœur des quartiers, à taille humaine. Les MJC sont des maisons où l'on pousse la porte sans rendez-vous.</p>
      </div>
    </div>
  </div>
</section>

<section style="background: var(--bg-card);">
  <div class="container">
    <div class="center reveal">
      <span class="section-eyebrow">Notre réseau</span>
      <h2>Sept maisons à Grenoble.</h2>
      <p style="max-width: 620px; margin: 1rem auto 0; color: var(--ink-soft);">
        Chaque MJC a son identité, son quartier, ses adhérents. Ensemble, elles forment l'Union Locale.
      </p>
    </div>
    <div class="stats reveal-stagger">
      <div class="stat">
        <span class="stat-num"><span data-count="1961">0</span></span>
        <span class="stat-label">Création de l'union</span>
      </div>
      <div class="stat">
        <span class="stat-num"><span data-count="7">0</span></span>
        <span class="stat-label">MJC et MPT membres</span>
      </div>
      <div class="stat">
        <span class="stat-num"><span data-count="60">0</span><span class="stat-suffix">+</span></span>
        <span class="stat-label">Années d'éduc pop</span>
      </div>
      <div class="stat">
        <span class="stat-num"><span data-count="100">0</span><span class="stat-suffix">&nbsp;%</span></span>
        <span class="stat-label">Bénévole</span>
      </div>
    </div>
    <div class="center" style="margin-top: 2.5rem;">
      <a href="les-mjc.php" class="btn">Voir les MJC/MPT</a>
    </div>
  </div>
</section>

<section style="background: var(--bg-soft);">
  <div class="container">
    <div class="split reveal">
      <div class="split-img split-img-portrait"><img src="images/chalet-neige.jpg" alt="Le chalet ULMJC sous la neige à l'Alpe du Grand Serre" loading="lazy"></div>
      <div>
        <span class="section-eyebrow">Et un chalet en montagne</span>
        <h2>Un refuge collectif, depuis 1963.</h2>
        <p>
          Au-delà des sept maisons, l'union locale fait vivre depuis 1963 son chalet à l'<strong>Alpe du Grand Serre</strong>,
          acquis pour permettre aux enfants et aux jeunes de découvrir la montagne.
        </p>
        <p>
          C'est aujourd'hui un espace de partage ouvert aux associations, aux familles, aux collectivités et aux groupes&nbsp;:
          jusqu'à 25 personnes, en gestion libre, géré par les bénévoles de l'union.
        </p>
        <a href="chalet.php" class="btn btn-accent">Visiter Le Chalet</a>
      </div>
    </div>
  </div>
</section>

<section>
  <div class="container center reveal">
    <span class="section-eyebrow">Rejoindre l'union</span>
    <h2>Poussez la porte d'une MJC.</h2>
    <p style="max-width: 620px; margin: 0 auto 2rem; color: var(--ink-soft);">
      Pour s'inscrire à une activité, proposer un projet, rejoindre un collectif — ou simplement passer dire bonjour.
      L'adhésion est solidaire et donne accès à toute la programmation locale.
    </p>
    <a href="les-mjc.php" class="btn">Trouver ma MJC</a>
    <a href="contact.php" class="btn btn-ghost" style="margin-left: 0.75rem;">Nous contacter</a>
  </div>
</section>

<?php include __DIR__ . '/inc/site-footer.php'; ?>

<script src="js/main.js?v=20260524-14"></script>

</body>
</html>
