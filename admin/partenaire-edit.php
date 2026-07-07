<?php
/* Éditeur de partenaire — calqué sur admin/edit.php, très simplifié (pas de corps
   HTML). Champs : nom, logo (upload OU médiathèque, même moule que la couverture
   d'actu), url, ordre, publié/masqué. */
require_once __DIR__ . '/auth.php';
require_login();

$id = isset($_GET['id']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['id']) : '';
$p  = null;
if ($id !== '') { foreach (load_partenaires() as $it) { if (($it['id'] ?? '') === $id) { $p = $it; break; } } }
$isNew = !$p;

$d = array(
  'id'        => $p['id']        ?? '',
  'nom'       => $p['nom']       ?? '',
  'logo'      => $p['logo']      ?? '',
  'url'       => $p['url']       ?? '',
  'ordre'     => $p['ordre']     ?? 0,
  'published' => $p['published'] ?? true,
);

admin_header($isNew ? 'Nouveau partenaire' : 'Modifier le partenaire');
?>
<div class="ahead">
  <h1 class="atitle"><?= $isNew ? 'Nouveau partenaire' : 'Modifier le partenaire' ?></h1>
  <a class="alink" href="partenaires.php">← Retour</a>
</div>

<form class="acard aform" method="post" action="partenaire-save.php" enctype="multipart/form-data" id="partForm">
  <?= csrf_field() ?>
  <input type="hidden" name="orig_id" value="<?= e($d['id']) ?>" />

  <label class="afield">Nom
    <input type="text" name="nom" value="<?= e($d['nom']) ?>" required placeholder="Ex. : Ville de Grenoble" />
  </label>

  <div class="agrid2">
    <label class="afield">Ordre d'affichage <span class="ahint">(plus petit = affiché en premier)</span>
      <input type="number" name="ordre" value="<?= e((string)$d['ordre']) ?>" step="1" />
    </label>
    <label class="afield aswitch-field">Statut
      <label class="aswitch">
        <input type="checkbox" name="published" value="1" <?= $d['published'] ? 'checked' : '' ?> />
        <span class="aswitch-track"><span class="aswitch-thumb"></span></span>
        <span class="aswitch-lbl">Publié (visible sur le site)</span>
      </label>
    </label>
  </div>

  <label class="afield">Lien du site <span class="ahint">(https://… — facultatif)</span>
    <input type="text" name="url" value="<?= e($d['url']) ?>" placeholder="https://www.exemple.fr" />
  </label>

  <div class="afield">Logo <span class="ahint">(facultatif — à défaut, le nom s'affiche)</span>
    <input type="hidden" name="cover" id="coverField" value="<?= e($d['logo']) ?>" />
    <input type="hidden" name="cover_remove" id="coverRemoveFlag" value="" />
    <div class="cover-preview" id="coverPreview"<?= $d['logo'] ? '' : ' hidden' ?> style="max-width:220px;background:#fff;">
      <img id="coverImg" src="<?= $d['logo'] ? e('../' . $d['logo']) : '' ?>" alt="" style="object-fit:contain;" />
    </div>
    <div class="hero-upload-row" style="margin-top:8px">
      <label class="abtn abtn-ghost">Importer un logo
        <input type="file" name="cover_file" accept="image/*" id="coverInput" />
      </label>
      <button type="button" class="abtn abtn-ghost" id="coverPickBtn">Choisir dans la médiathèque</button>
      <button type="button" class="alink adanger" id="coverRemoveBtn"<?= $d['logo'] ? '' : ' hidden' ?>>Retirer le logo</button>
    </div>
    <span class="secphoto-fname" id="coverFileName"></span>
    <span class="ahint">JPG ou PNG. Redimensionné automatiquement à l'enregistrement.</span>
  </div>

  <div class="aactions">
    <button class="abtn" type="submit">Enregistrer</button>
    <a class="alink" href="partenaires.php">Annuler</a>
  </div>
</form>

<script>
(function(){
  // Logo : upload (fichier) OU médiathèque — même logique que la couverture d'actu.
  var coverField=document.getElementById('coverField');
  var coverInput=document.getElementById('coverInput');
  var coverPreview=document.getElementById('coverPreview');
  var coverImg=document.getElementById('coverImg');
  var coverRemoveFlag=document.getElementById('coverRemoveFlag');
  var coverRemoveBtn=document.getElementById('coverRemoveBtn');
  var coverFileName=document.getElementById('coverFileName');
  function showCover(src){ coverImg.src=src; coverPreview.hidden=false; coverRemoveBtn.hidden=false; }
  document.getElementById('coverPickBtn').addEventListener('click',function(){
    if(!window.openMediaPicker){ alert('Médiathèque indisponible.'); return; }
    window.openMediaPicker(function(src){
      coverField.value=src; coverRemoveFlag.value=''; coverInput.value='';
      coverFileName.textContent=''; showCover('../'+src);
    });
  });
  coverInput.addEventListener('change',function(){
    var f=coverInput.files&&coverInput.files[0]; if(!f)return;
    coverField.value=''; coverRemoveFlag.value='';
    coverFileName.textContent=f.name;
    var rd=new FileReader(); rd.onload=function(){ showCover(rd.result); }; rd.readAsDataURL(f);
  });
  coverRemoveBtn.addEventListener('click',function(){
    coverField.value=''; coverInput.value=''; coverRemoveFlag.value='1';
    coverFileName.textContent=''; coverImg.src=''; coverPreview.hidden=true; coverRemoveBtn.hidden=true;
  });
})();
</script>
<?php admin_footer(); ?>
