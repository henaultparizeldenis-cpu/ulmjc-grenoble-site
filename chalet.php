<?php
/* Page publique : Le chalet. Version PHP de l'ancien chalet.html.
   SEULE différence de fond : les 5 cartes « catégorie » et leurs listes de photos
   sont générées DEPUIS chalet.json (helper load_gallery()) au lieu d'être codées en
   dur. Chaque bouton expose la liste des CHEMINS d'images dans data-photos
   (ex. « images/chalet/chalet-01.jpg,uploads/ab.jpg ») — le handler lightbox de
   js/main.js lit directement ces chemins (voir la modif dans main.js).
   Le reste (présentation, météo, tarifs, accès…) est repris tel quel de chalet.html,
   avec head.php/foot.php pour l'en-tête/pied communs. */
require_once __DIR__ . '/inc/lib.php';

$page_title  = 'Le chalet — ULMJC Grenoble';
$page_desc   = "Le chalet de l'ULMJC Grenoble, à l'Alpe du Grand Serre (1368 m) : 25 places en gestion libre, ouvert aux associations, collectivités, familles et groupes. Tarifs 2026/2027.";
$page_active = 'chalet';

$gallery = load_gallery();
$cats    = chalet_categories();

/* Texte descriptif de chaque carte (repris de chalet.html). */
$catText = array(
  'exterieur'     => "Le chalet sous toutes ses faces, le jardin, la vue sur le massif du Taillefer et l'Alpe du Grand Serre.",
  'couchage'      => "À l'étage : 2 dortoirs séparés (13 et 12 places) + 1 chambre double avec sanitaires indépendants.",
  'sanitaires'    => "2 blocs sanitaires à l'étage (lavabos, douches, WC) + 1 toilette avec lavabo au rez-de-chaussée.",
  'cuisine'       => "Cuisine équipée : gazinière, plan de travail, frigo, congélateur, four micro-ondes, vaisselle complète.",
  'pieces-de-vie' => "Grand réfectoire / salle de séjour au rez-de-chaussée. Cellier et local à skis. Ambiance chalet de montagne.",
);

/* Photo de présentation (split) : 1re photo « extérieur » si dispo, sinon repli. */
$splitImg = !empty($gallery['exterieur'][0]) ? $gallery['exterieur'][0] : 'images/chalet/chalet-24.jpg';

require __DIR__ . '/inc/head.php';
?>

<div class="page-header">
  <div class="container">
    <span class="section-eyebrow">Le chalet · Alpe du Grand Serre</span>
    <h1>Notre chalet, depuis 1963.</h1>
    <p class="lede">Un chalet atypique à 1368 m, à 45 min de Grenoble, aux portes de l'Oisans. En gestion libre, ouvert aux associations, collectivités, familles, jeunes et camps — toute l'année.</p>
  </div>
</div>

<section>
  <div class="container">
    <div class="split reveal">
      <div class="split-img"><img src="<?= e($splitImg) ?>" alt="Façade du chalet ULMJC à l'Alpe du Grand Serre en été" loading="lazy"></div>
      <div>
        <h2>Présentation</h2>
        <p>
          Le chalet est situé dans la commune de <strong>La Morte (Isère)</strong>, sur la station familiale de l'<strong>Alpe du Grand Serre</strong>,
          à 1368&nbsp;m d'altitude. Il est niché aux Portes de l'Oisans, au pied du massif du Taillefer et du Grand Serre,
          dans une <strong>zone naturelle protégée</strong>.
        </p>
        <p>
          Acquis en 1963 par l'Union Locale des MJC de Grenoble, il a été pensé dès l'origine comme un lieu de séjour collectif pour les
          enfants, les jeunes et les groupes. Plus de 60 ans après, il continue d'accueillir des générations d'associations,
          de familles et de camps dans le même esprit.
        </p>
        <p class="muted">
          📍 1407 route du Désert, 38350 La Morte — Alpe du Grand Serre
        </p>
      </div>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="chalet-weather reveal" data-chalet-weather>
      <div class="weather-card">
        <div class="weather-card-header">
          <span class="section-eyebrow">Météo en direct</span>
          <h2>Aujourd'hui au chalet.</h2>
        </div>
        <div class="weather-loading">Chargement de la météo…</div>
        <div class="weather-content" hidden>
          <div class="weather-main">
            <span class="weather-emoji" data-emoji>☀️</span>
            <div>
              <div class="weather-temp"><span data-temp>—</span><span class="weather-unit">°C</span></div>
              <div class="weather-condition" data-condition>—</div>
            </div>
          </div>
          <div class="weather-details">
            <div class="weather-detail">
              <span class="weather-detail-label">Ressenti</span>
              <span class="weather-detail-value"><span data-feels>—</span>°C</span>
            </div>
            <div class="weather-detail">
              <span class="weather-detail-label">Min · Max</span>
              <span class="weather-detail-value"><span data-tmin>—</span>° / <span data-tmax>—</span>°C</span>
            </div>
            <div class="weather-detail">
              <span class="weather-detail-label">Vent</span>
              <span class="weather-detail-value"><span data-wind>—</span>&nbsp;km/h</span>
            </div>
            <div class="weather-detail" data-snow-block hidden>
              <span class="weather-detail-label">Neige du jour</span>
              <span class="weather-detail-value"><span data-snow>—</span>&nbsp;cm</span>
            </div>
          </div>
          <p class="weather-meta">À l'Alpe du Grand Serre · 1368&nbsp;m · données <a href="https://open-meteo.com" target="_blank" rel="noopener">Open-Meteo</a></p>
        </div>
      </div>
    </div>
  </div>
</section>

<section style="background: var(--bg-soft);">
  <div class="container">
    <div class="center reveal">
      <span class="section-eyebrow">Configuration</span>
      <h2>Le chalet en détail.</h2>
    </div>
    <p class="muted center" style="margin-top: 0; margin-bottom: 2rem; font-size: 0.95rem;">
      Cliquez sur une catégorie pour voir les photos en grand.
    </p>
    <div class="cards reveal-stagger chalet-cards">
      <?php foreach ($cats as $key => $meta):
        $photos = isset($gallery[$key]) ? $gallery[$key] : array();
        if (!$photos) continue; // catégorie sans photo : on n'affiche pas la carte
        $label = $meta['label'];
        ?>
      <button type="button" class="card chalet-card" data-photos="<?= e(implode(',', $photos)) ?>" aria-label="Voir les photos : <?= e($label) ?>">
        <div class="icon"><?= $meta['icon'] ?></div>
        <h3><?= e($label) ?></h3>
        <p><?= e($catText[$key] ?? '') ?></p>
        <span class="card-link">Voir les photos</span>
      </button>
      <?php endforeach; ?>
    </div>
    <p class="muted center" style="margin-top: 2rem; font-size: 0.95rem;">
      <strong>Capacité&nbsp;:</strong> 25 personnes adultes maximum — ou 20 mineurs de + de 6 ans accompagnés d'adultes (agrément SDJES).<br>
      ⚠️ Le chalet n'est pas adapté aux personnes à mobilité réduite.
    </p>
  </div>
</section>

<section>
  <div class="container prose reveal">
    <h2>Pour qui&nbsp;?</h2>
    <p>
      Le chalet est ouvert <strong>toute l'année</strong>, en semaine ou en week-end, pour&nbsp;:
    </p>
    <ul>
      <li>les <strong>associations</strong> (séminaires, week-ends, formations)</li>
      <li>les <strong>collectivités</strong> (classes découverte, séjours jeunesse)</li>
      <li>les <strong>particuliers</strong> : réunions de famille, vacances, anniversaires</li>
      <li>les <strong>camps de mineurs</strong> (agrément SDJES)</li>
    </ul>

    <h2>Tarifs 2026/2027</h2>
    <div class="prose-callout">
      <p><strong>Adhésion&nbsp;:</strong> 10&nbsp;€ par groupe (quel que soit le nombre de participants)</p>
      <p><strong>Nuitée&nbsp;:</strong> 289&nbsp;€ par nuit jusqu'à 17 personnes</p>
      <p><strong>Au-delà de 17 personnes&nbsp;:</strong> 289&nbsp;€ + 17&nbsp;€/jour/personne supplémentaire (dans la limite de 25)</p>
      <p class="muted" style="margin-top: 0.5rem;">+ Taxe de séjour, uniquement pour les adultes.</p>
    </div>

    <h2>Réservations</h2>
    <p>Pour vérifier les disponibilités et réserver, contactez-nous directement&nbsp;:</p>
    <ul>
      <li>📞 <strong>06 81 71 97 99</strong> ou <strong>06 88 90 35 84</strong></li>
      <li>✉️ <a href="mailto:ulmjc.gre@free.fr"><strong>ulmjc.gre@free.fr</strong></a></li>
    </ul>
    <p style="margin-top: 1.5rem;">
      <a href="contact.html" class="btn btn-accent">Nous contacter</a>
    </p>

    <h2>Restauration</h2>
    <p>
      La cuisine équipée du chalet vous permet de gérer vos propres repas. Vous pouvez aussi faire appel
      au traiteur partenaire <strong>« Verveine Citron »</strong>&nbsp;:
    </p>
    <ul>
      <li>📞 06 14 58 68 23</li>
      <li>✉️ <a href="mailto:verv.citron@gmail.com">verv.citron@gmail.com</a></li>
      <li>Réservation au plus tard 10 jours avant l'arrivée.</li>
    </ul>
    <p class="muted" style="font-size: 0.92rem;">
      <em>Cette prestation est proposée à titre indicatif et relève d'un prestataire extérieur, sans engagement ni responsabilité du chalet.</em>
    </p>

    <h2>Accès et services à proximité</h2>
    <p><strong>Office du tourisme</strong> Alpe du Grand Serre&nbsp;: 04&nbsp;76&nbsp;56&nbsp;24&nbsp;72 — <em>navettes disponibles pendant la saison de ski.</em></p>
    <p><strong>Commerces en station</strong> : Vincent Sports Intersports, Sherpa Alimentation (Relais La Poste, distributeur, gaz), La Case à Skis,
      La Ferme du Grand Rif, Les Petits Soins de Marie, Les Typotes Vagabondes de La Morte. Restaurants et bar en station.</p>
    <p>Voir aussi la page <a href="activites.php">Activités</a> pour les loisirs autour du chalet.</p>
  </div>
</section>

<?php require __DIR__ . '/inc/foot.php'; ?>
