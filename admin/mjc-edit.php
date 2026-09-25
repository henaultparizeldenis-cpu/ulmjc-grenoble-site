<?php
/* Éditeur d'une maison, calqué sur admin/partenaire-edit.php.

   Champs : nom, quartier, adresse, antennes, téléphone, courriel, site, logo
   (upload OU médiathèque), ordre, publié. Plus les coordonnées, qui décident
   de la présence sur la carte.

   Les coordonnées ne se saisissent pas à la main : l'enregistrement les
   demande à la Base Adresse Nationale à partir de l'adresse. On les affiche
   quand même, et on les laisse modifiables, parce qu'une adresse mal
   géocodée doit pouvoir être rattrapée sans toucher au code. */
require_once __DIR__ . '/auth.php';
require_login();

$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']) : '';
$m    = null;
if ($slug !== '') { foreach (load_mjc() as $it) { if (($it['slug'] ?? '') === $slug) { $m = $it; break; } } }
$isNew = !$m;

$d = array(
  'slug'      => $m['slug']      ?? '',
  'nom'       => $m['nom']       ?? '',
  'quartier'  => $m['quartier']  ?? '',
  'adresse'   => $m['adresse']   ?? '',
  'lieu'      => $m['lieu']      ?? '',
  'antennes'  => $m['antennes']  ?? '',
  'tel'       => $m['tel']       ?? '',
  'email'     => $m['email']     ?? '',
  'site'      => $m['site']      ?? '',
  'logo'      => $m['logo']      ?? '',
  'lat'       => $m['lat']       ?? '',
  'lon'       => $m['lon']       ?? '',
  'ordre'     => $m['ordre']     ?? 0,
  'published' => $m['published'] ?? true,
);

admin_header($isNew ? 'Nouvelle maison' : 'Modifier la maison');
?>
<div class="ahead">
  <h1 class="atitle"><?= $isNew ? 'Nouvelle maison' : 'Modifier la maison' ?></h1>
  <a class="alink" href="mjc.php">← Retour</a>
</div>

<form class="acard aform" method="post" action="mjc-save.php" enctype="multipart/form-data" id="mjcForm">
  <?= csrf_field() ?>
  <input type="hidden" name="orig_slug" value="<?= e($d['slug']) ?>" />

  <label class="afield">Nom
    <input type="text" name="nom" value="<?= e($d['nom']) ?>" required placeholder="Ex. : MJC Allobroges" />
  </label>

  <div class="agrid2">
    <label class="afield">Quartier <span class="ahint">(séparez par «&nbsp;/&nbsp;» s'il y en a plusieurs)</span>
      <input type="text" name="quartier" value="<?= e($d['quartier']) ?>" placeholder="Ex. : Notre-Dame / Île Verte" />
    </label>
    <label class="afield">Ordre d'affichage <span class="ahint">(plus petit = affiché en premier)</span>
      <input type="number" name="ordre" value="<?= e((string)$d['ordre']) ?>" step="1" />
    </label>
  </div>

  <label class="afield">Adresse <span class="ahint">(rue, code postal et commune&nbsp;: elle sert aussi à placer la maison sur la carte)</span>
    <input type="text" name="adresse" value="<?= e($d['adresse']) ?>" placeholder="Ex. : 1 rue Hauquelin, 38000 Grenoble" />
  </label>

  <label class="afield">Antennes <span class="ahint">(facultatif, affichées sous l'adresse)</span>
    <input type="text" name="antennes" value="<?= e($d['antennes']) ?>" placeholder="Ex. : Waldeck-Rousseau, École Simone Lagrange" />
  </label>

  <div class="agrid2">
    <label class="afield">Téléphone
      <input type="text" name="tel" value="<?= e($d['tel']) ?>" placeholder="Ex. : 04 76 42 56 96" />
    </label>
    <label class="afield">Courriel
      <input type="email" name="email" value="<?= e($d['email']) ?>" placeholder="Ex. : contact@exemple.org" />
    </label>
  </div>

  <label class="afield">Site internet <span class="ahint">(https://…, facultatif)</span>
    <input type="text" name="site" value="<?= e($d['site']) ?>" placeholder="https://www.exemple.fr" />
  </label>

  <fieldset class="afield" style="border:1px solid var(--aline,#e2ddd4);border-radius:8px;padding:12px 14px;">
    <legend class="ahint" style="padding:0 6px;">Position sur la carte</legend>
    <p class="ahint" style="margin:0 0 10px;">
      Laissez ces cases vides&nbsp;: elles seront remplies automatiquement à partir
      de l'adresse, à l'enregistrement. Ne les remplissez à la main que si le point
      tombe au mauvais endroit.
    </p>
    <div class="agrid2">
      <label class="afield">Latitude
        <input type="text" name="lat" value="<?= e((string)$d['lat']) ?>" placeholder="calculée automatiquement" />
      </label>
      <label class="afield">Longitude
        <input type="text" name="lon" value="<?= e((string)$d['lon']) ?>" placeholder="calculée automatiquement" />
      </label>
    </div>
    <label class="afield">Libellé du point <span class="ahint">(facultatif, affiché sur la carte)</span>
      <input type="text" name="lieu" value="<?= e($d['lieu']) ?>" placeholder="Ex. : 1 rue Hauquelin" />
    </label>
  </fieldset>

  <label class="afield aswitch-field">Statut
    <label class="aswitch">
      <input type="checkbox" name="published" value="1" <?= $d['published'] ? 'checked' : '' ?> />
      <span class="aswitch-track"><span class="aswitch-thumb"></span></span>
      <span class="aswitch-lbl">Publiée (visible sur le site et sur la carte)</span>
    </label>
  </label>

  <div class="afield">Logo <span class="ahint">(facultatif, à défaut le nom s'affiche)</span>
    <input type="hidden" name="cover" id="coverField" value="<?= e($d['logo']) ?>" />
    <input type="hidden" name="cover_remove" id="coverRemoveFlag" value="" />
    <div class="cover-preview" id="coverPreview"<?= $d['logo'] ? '' : ' hidden' ?> style="max-width:220px;background:#fff;">
      <img id="coverImg" src="<?= $d['logo'] ? e('../' . $d['logo']) : '' ?>" alt="" style="object-fit:contain;" />
    </div>
    <div class="hero-upload-row" style="margin-top:8px">
      <input type="file" name="cover_file" id="coverFile" accept="image/*" />
      <button type="button" class="alink" id="coverClear">Retirer le logo</button>
    </div>
  </div>

  <div class="aform-actions">
    <button class="abtn" type="submit">Enregistrer</button>
    <a class="alink" href="mjc.php">Annuler</a>
  </div>
</form>

<script>
/* Même logique que l'éditeur de partenaire : l'aperçu suit le fichier choisi,
   et « Retirer » pose un drapeau que l'enregistrement lit. */
(function () {
  var f = document.getElementById('coverFile');
  var img = document.getElementById('coverImg');
  var box = document.getElementById('coverPreview');
  var clear = document.getElementById('coverClear');
  var flag = document.getElementById('coverRemoveFlag');
  var field = document.getElementById('coverField');
  if (f) f.addEventListener('change', function () {
    if (!f.files || !f.files[0]) return;
    img.src = URL.createObjectURL(f.files[0]);
    box.hidden = false;
    flag.value = '';
  });
  if (clear) clear.addEventListener('click', function () {
    if (f) f.value = '';
    img.src = '';
    box.hidden = true;
    field.value = '';
    flag.value = '1';
  });
})();
</script>
<?php admin_footer(); ?>
