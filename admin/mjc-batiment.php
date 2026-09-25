<?php
/* Désigner le bâtiment d'une maison, en le cliquant sur la carte.

   Sans cette désignation, la carte surligne le bâtiment le plus proche de
   l'adresse. Ce n'est qu'une déduction, et elle se trompe : les adresses sont
   géocodées sur la voie, aucune maison ne tombe dans une emprise bâtie, la
   plus proche étant entre dix et quarante mètres, et dans un quartier dense
   plusieurs se valent. Deux des bâtiments ainsi déduits font moins de cinq
   mètres de haut : ce sont des appentis.

   Ici on réutilise le moteur de la page publique (js/carte-mjc.js) en mode
   « designe » : les fiches sont rendues masquées, uniquement pour que la
   carte y lise les coordonnées, et un clic sur un toit remonte l'identifiant
   BD TOPO du bâtiment. */
require_once __DIR__ . '/auth.php';
require_login();

$maisons = active_items('mjc');
usort($maisons, 'cmp_ordre');
$maisons = array_values(array_filter($maisons, function ($m) {
  return is_numeric($m['lat'] ?? null) && is_numeric($m['lon'] ?? null);
}));

$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']) : '';
$depart = 0;
foreach ($maisons as $i => $m) { if (($m['slug'] ?? '') === $slug) { $depart = $i; break; } }

$faits = 0;
foreach ($maisons as $m) { if (!empty($m['batiment'])) $faits++; }

admin_header('Désigner les bâtiments');
?>
<div class="ahead">
  <div>
    <h1 class="atitle">Désigner les bâtiments</h1>
    <p class="asub"><?= $faits ?> maison<?= $faits > 1 ? 's' : '' ?> sur <?= count($maisons) ?> désignée<?= $faits > 1 ? 's' : '' ?></p>
  </div>
  <a class="alink" href="mjc.php">← Retour</a>
</div>

<?php if (!$maisons): ?>
  <div class="acard aempty">Aucune maison n'a de coordonnées : rien à désigner.</div>
<?php else: ?>

<div class="acard" style="padding:1rem 1.1rem;">
  <p class="ahint" style="margin:0 0 .8rem;">
    Choisissez une maison, la carte descend dessus. Le bâtiment en terre cuite est
    celui que la carte suppose. <strong>Cliquez le bon</strong>, puis enregistrez.
    Tirez pour tourner, la molette grossit, double-clic pour descendre au ras des toits.
  </p>

  <div class="agrid2" style="align-items:end;">
    <label class="afield">Maison
      <select id="choixMaison">
        <?php foreach ($maisons as $i => $m): ?>
          <option value="<?= $i ?>"<?= $i === $depart ? ' selected' : '' ?>>
            <?= e($m['nom']) ?><?= !empty($m['batiment']) ? ' (désigné)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <div>
      <p class="ahint" id="etat" style="margin:0 0 .5rem;">Aucun bâtiment choisi.</p>
      <form method="post" action="mjc-batiment-save.php" id="formBat" style="display:flex;gap:.6rem;align-items:center;">
        <?= csrf_field() ?>
        <input type="hidden" name="slug" id="champSlug" value="" />
        <input type="hidden" name="batiment" id="champBat" value="" />
        <button class="abtn" type="submit" id="btnEnr" disabled>Enregistrer ce bâtiment</button>
        <button class="alink adanger" type="submit" name="vider" value="1">Effacer la désignation</button>
      </form>
    </div>
  </div>
</div>

<div id="mjc-carte" class="mjc-carte" data-mode="designe" style="margin-top:1rem;"></div>

<?php /* Les fiches, masquées : la carte y lit les coordonnées et les noms.
         C'est le même contrat que sur la page publique, donc un seul moteur. */ ?>
<ul class="mjc-list" hidden>
  <?php foreach ($maisons as $m):
    $pt = array(array('lat' => (float)$m['lat'], 'lon' => (float)$m['lon']));
    if (!empty($m['lieu'])) $pt[0]['lieu'] = $m['lieu']; ?>
    <li class="mjc-item" data-points='<?= e(json_encode($pt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'<?php
      if (!empty($m['batiment'])): ?> data-batiment="<?= e($m['batiment']) ?>"<?php endif; ?>>
      <div class="mjc-info">
        <h2><?= e($m['nom']) ?></h2>
        <p class="mjc-quartier"><?= e($m['quartier'] ?? '') ?></p>
        <p class="mjc-address"><?= e($m['adresse'] ?? '') ?></p>
      </div>
    </li>
  <?php endforeach; ?>
</ul>

<link rel="stylesheet" href="../css/style.css?v=20260925-2">
<script src="../js/carte-mjc.js?v=20260925-2"></script>
<script>
(function () {
  var MAISONS = <?= json_encode(array_map(function ($m) {
    return array('slug' => $m['slug'], 'nom' => $m['nom'], 'batiment' => $m['batiment'] ?? '');
  }, $maisons), JSON_UNESCAPED_UNICODE) ?>;

  var choix = document.getElementById('choixMaison');
  var etat  = document.getElementById('etat');
  var btn   = document.getElementById('btnEnr');
  var cSlug = document.getElementById('champSlug');
  var cBat  = document.getElementById('champBat');

  function suit() {
    var m = MAISONS[+choix.value];
    cSlug.value = m.slug;
    cBat.value = '';
    btn.disabled = true;
    etat.textContent = m.batiment
      ? 'Bâtiment déjà désigné (n° ' + m.batiment + '). Cliquez-en un autre pour le remplacer.'
      : 'Aucun bâtiment désigné : la carte montre celui qu\'elle suppose.';
    /* On plonge sur la maison choisie. La carte expose viseParIndex pour
       cela : c'est la même descente qu'un clic sur un jalon. */
    if (window.viseParIndex) window.viseParIndex(+choix.value, true);
  }
  choix.addEventListener('change', suit);

  window.onBatimentChoisi = function (id, hauteur) {
    cBat.value = id;
    btn.disabled = false;
    etat.textContent = 'Bâtiment n° ' + id + ', ' + hauteur + ' m de haut. Enregistrez pour le retenir.';
  };

  /* La carte met un moment à charger son relief et son bâti. */
  var attente = setInterval(function () {
    if (window.viseParIndex) { clearInterval(attente); suit(); }
  }, 200);
})();
</script>
<?php endif; ?>
<?php admin_footer(); ?>
