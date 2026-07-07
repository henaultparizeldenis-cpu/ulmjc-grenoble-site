<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Contacter l'Union Locale des MJC de Grenoble : téléphones, email et formulaire pour réserver le chalet de l'Alpe du Grand Serre.">
<title>Contact — ULMJC Grenoble</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="canonical" href="https://site.ulmjcgrenoble.org/contact.php">
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
</head>
<body>

<?php $active = 'contact'; include __DIR__ . '/inc/nav.php'; ?>

<div class="page-header">
  <div class="container">
    <span class="section-eyebrow">Contact</span>
    <h1>Nous écrire.</h1>
    <p class="lede">Que ce soit pour réserver le chalet, proposer un projet, poser une question ou simplement en savoir plus sur l'association : on lit tous les messages.</p>
  </div>
</div>

<section>
  <div class="container">
    <div class="split reveal">
      <div>
        <h2>Réserver le chalet</h2>
        <p>Pour vérifier les disponibilités et réserver, le plus simple est de nous appeler ou de nous écrire&nbsp;:</p>
        <p>
          <strong>📞 Téléphone</strong><br>
          <a href="tel:+33681719799">06 81 71 97 99</a><br>
          <a href="tel:+33688903584">06 88 90 35 84</a>
        </p>
        <p>
          <strong>✉️ Email</strong><br>
          <a href="mailto:ulmjc.gre@free.fr">ulmjc.gre@free.fr</a>
        </p>
        <p class="muted" style="font-size: 0.95rem;">
          Adhésion 10&nbsp;€ par groupe — 289&nbsp;€ la nuitée jusqu'à 17 personnes.
          Voir la page <a href="chalet.php">Chalet</a> pour la grille tarifaire complète.
        </p>

        <h3 style="margin-top: 2.5rem;">L'association</h3>
        <p>
          <strong>Union Locale des Maisons de la Jeunesse et de la Culture de Grenoble</strong><br>
          Association loi 1901 d'éducation populaire
        </p>
        <p>
          <strong>Siège social</strong><br>
          Maison de la vie associative et citoyenne<br>
          6 rue Berthe de Boissieux<br>
          38000 Grenoble
        </p>
        <p class="muted" style="font-size: 0.92rem;">
          RNA&nbsp;: W381 028 645<br>
          SIRET&nbsp;: 933 229 528 00015<br>
          TVA intracom.&nbsp;: FR03 933 229 528
        </p>

        <h3 style="margin-top: 2.5rem;">Le chalet</h3>
        <p>
          <strong>Adresse</strong><br>
          1407 route du Désert<br>
          38350 La Morte — Alpe du Grand Serre<br>
          <span class="muted">À 45 min de Grenoble (Isère)</span>
        </p>

        <h3 style="margin-top: 2.5rem;">Traiteur partenaire</h3>
        <p>
          <strong>Verveine Citron</strong> — sur réservation 10 j avant l'arrivée<br>
          📞 <a href="tel:+33614586823">06 14 58 68 23</a> · ✉️ <a href="mailto:verv.citron@gmail.com">verv.citron@gmail.com</a>
        </p>
        <p class="muted" style="font-size: 0.88rem;">
          <em>Prestataire extérieur indépendant — l'association ne gère pas la prestation.</em>
        </p>
      </div>

      <div>
        <h2>Formulaire</h2>
        <form class="form" action="https://formspree.io/f/REMPLACER_PAR_VOTRE_ID" method="POST">
          <div>
            <label for="name">Votre nom</label>
            <input type="text" id="name" name="name" required>
          </div>
          <div>
            <label for="email">Votre email</label>
            <input type="email" id="email" name="email" required>
          </div>
          <div>
            <label for="subject">Sujet</label>
            <input type="text" id="subject" name="subject" placeholder="Réservation, projet, question…">
          </div>
          <div>
            <label for="message">Message</label>
            <textarea id="message" name="message" required placeholder="Si c'est pour réserver : dates envisagées, type de groupe, nombre de personnes adultes / mineurs."></textarea>
          </div>
          <div>
            <button type="submit" class="btn btn-accent">Envoyer</button>
          </div>
        </form>
        <p class="muted" style="font-size: 0.88rem; margin-top: 1rem;">
          <em>Le formulaire est pour l'instant inactif — il faut créer un compte gratuit sur Formspree.io et coller l'identifiant dans le code.</em>
        </p>

      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/inc/site-footer.php'; ?>

<script src="js/main.js?v=20260524-14"></script>

</body>
</html>
