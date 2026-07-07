<?php
/* Éditeur d'actualité — VERSION SIMPLE pour bénévoles.
   Basé sur mohamed-cms/site/admin/edit.php, fortement simplifié :
   titre, date, image de couverture (upload OU médiathèque), accroche (excerpt),
   chapô, corps (texte enrichi simple avec insertion d'images depuis la
   médiathèque), interrupteur publié/brouillon. Pas de filtres/effets photo, pas
   de galeries, pas de réglage de largeur de couverture (volontairement épuré). */
require_once __DIR__ . '/auth.php';
require_login();

$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']) : '';
$a = $slug ? find_actu($slug) : null;
$isNew = !$a;

$d = array(
  'slug'      => $a['slug']      ?? '',
  'title'     => $a['title']     ?? '',
  'date'      => $a['date']      ?? date('Y-m-d'),
  'excerpt'   => $a['excerpt']   ?? '',
  'chapo'     => $a['chapo']     ?? '',
  'cover'     => $a['cover']     ?? '',
  'body'      => $a['body']      ?? '',
  'published' => $a['published'] ?? true,
);

// Dans l'éditeur (sous /admin/), les images doivent pointer un cran plus haut.
$editorBody = str_replace(array('src="uploads/', 'src="images/'), array('src="../uploads/', 'src="../images/'), $d['body']);

admin_header($isNew ? 'Nouvelle actualité' : 'Modifier l\'actualité');
?>
<div class="ahead">
  <h1 class="atitle"><?= $isNew ? 'Nouvelle actualité' : 'Modifier l\'actualité' ?></h1>
  <a class="alink" href="index.php">← Retour</a>
</div>

<form class="acard aform" method="post" action="save.php" enctype="multipart/form-data" id="actuForm">
  <?= csrf_field() ?>
  <input type="hidden" name="orig_slug" value="<?= e($d['slug']) ?>" />

  <label class="afield">Titre
    <input type="text" name="title" value="<?= e($d['title']) ?>" required placeholder="Ex. : Assemblée générale le 12 octobre" />
  </label>

  <div class="agrid2">
    <label class="afield">Date
      <input type="date" name="date" value="<?= e($d['date']) ?>" required />
    </label>
    <label class="afield aswitch-field">Statut
      <label class="aswitch">
        <input type="checkbox" name="published" value="1" <?= $d['published'] ? 'checked' : '' ?> />
        <span class="aswitch-track"><span class="aswitch-thumb"></span></span>
        <span class="aswitch-lbl">Publiée (visible sur le site)</span>
      </label>
    </label>
  </div>

  <label class="afield">Accroche courte <span class="ahint">(résumé affiché dans la liste — 1 phrase)</span>
    <input type="text" name="excerpt" value="<?= e($d['excerpt']) ?>" maxlength="200" placeholder="Une phrase qui donne envie de lire." />
  </label>

  <label class="afield">Chapô <span class="ahint">(introduction en italique en haut de l'article — facultatif)</span>
    <textarea name="chapo" rows="2" placeholder="L'idée forte de l'actualité, en une ou deux phrases."><?= e($d['chapo']) ?></textarea>
  </label>

  <div class="afield">Image de couverture <span class="ahint">(facultatif)</span>
    <input type="hidden" name="cover" id="coverField" value="<?= e($d['cover']) ?>" />
    <input type="hidden" name="cover_remove" id="coverRemoveFlag" value="" />
    <div class="cover-preview" id="coverPreview"<?= $d['cover'] ? '' : ' hidden' ?>>
      <img id="coverImg" src="<?= $d['cover'] ? e('../' . $d['cover']) : '' ?>" alt="" />
    </div>
    <div class="hero-upload-row" style="margin-top:8px">
      <label class="abtn abtn-ghost">Importer une photo
        <input type="file" name="cover_file" accept="image/*" id="coverInput" />
      </label>
      <button type="button" class="abtn abtn-ghost" id="coverPickBtn">Choisir dans la médiathèque</button>
      <button type="button" class="alink adanger" id="coverRemoveBtn"<?= $d['cover'] ? '' : ' hidden' ?>>Retirer la photo</button>
    </div>
    <span class="secphoto-fname" id="coverFileName"></span>
    <span class="ahint">JPG ou PNG. Redimensionnée automatiquement à l'enregistrement.</span>
  </div>

  <div class="afield">Corps de l'actualité
    <div class="editor-toolbar" id="editorToolbar" role="toolbar" aria-label="Mise en forme">
      <button type="button" data-cmd="formatBlock" data-val="h2" title="Titre de section">H2</button>
      <button type="button" data-cmd="formatBlock" data-val="h3" title="Sous-titre">H3</button>
      <button type="button" data-cmd="formatBlock" data-val="p" title="Paragraphe normal">¶</button>
      <span class="editor-sep"></span>
      <button type="button" data-cmd="bold" title="Gras"><strong>B</strong></button>
      <button type="button" data-cmd="italic" title="Italique"><em>I</em></button>
      <button type="button" data-cmd="formatBlock" data-val="blockquote" title="Citation">❝</button>
      <span class="editor-sep"></span>
      <button type="button" data-cmd="insertUnorderedList" title="Liste à puces">• Liste</button>
      <button type="button" data-cmd="createLink" title="Lien">🔗 Lien</button>
      <button type="button" id="insertImageBtn" title="Insérer une image">🖼️ Image</button>
    </div>
    <div class="editor-area" id="editorArea" contenteditable="true"><?= $editorBody ?></div>
    <textarea name="body" id="bodyField" hidden></textarea>
    <span class="ahint">Sélectionnez du texte puis cliquez sur un bouton. « Image » insère une photo de la médiathèque à l'endroit du curseur.</span>
  </div>

  <div class="aactions">
    <button class="abtn" type="submit" id="saveBtn">Enregistrer</button>
    <a class="alink" href="index.php">Annuler</a>
  </div>
</form>

<script>
(function(){
  var form=document.getElementById('actuForm');
  var area=document.getElementById('editorArea');
  var bodyField=document.getElementById('bodyField');
  var toolbar=document.getElementById('editorToolbar');

  // Barre d'outils : execCommand simple (contenteditable). Suffisant pour des
  // bénévoles ; le corps est de toute façon re-nettoyé côté serveur (sanitize_body).
  toolbar.addEventListener('click',function(e){
    var btn=e.target.closest('button[data-cmd]'); if(!btn)return;
    e.preventDefault(); area.focus();
    var cmd=btn.getAttribute('data-cmd'), val=btn.getAttribute('data-val')||null;
    if(cmd==='createLink'){
      var url=prompt('Adresse du lien (https://…)'); if(!url)return;
      document.execCommand('createLink',false,url); return;
    }
    document.execCommand(cmd,false,val);
  });

  // Insertion d'une image depuis la médiathèque, à l'endroit du curseur.
  var savedRange=null;
  area.addEventListener('keyup',saveSel); area.addEventListener('mouseup',saveSel);
  function saveSel(){ var s=window.getSelection(); if(s.rangeCount&&area.contains(s.anchorNode)) savedRange=s.getRangeAt(0); }
  document.getElementById('insertImageBtn').addEventListener('click',function(){
    if(!window.openMediaPicker){ alert('Médiathèque indisponible.'); return; }
    window.openMediaPicker(function(src){
      area.focus();
      var sel=window.getSelection();
      if(savedRange){ sel.removeAllRanges(); sel.addRange(savedRange); }
      var fig=document.createElement('figure'); fig.className='a-img';
      var img=document.createElement('img'); img.src='../'+src; img.alt='';
      fig.appendChild(img);
      document.execCommand('insertHTML',false,fig.outerHTML+'<p><br></p>');
    });
  });

  // Couverture : upload (fichier) OU médiathèque. Un seul champ « cover » stocke
  // le chemin choisi dans la médiathèque ; un upload prend le pas côté serveur.
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

  // À l'envoi : recopie le HTML de l'éditeur (chemins ramenés en « uploads/ » /
  // « images/ ») dans le champ caché envoyé au serveur.
  form.addEventListener('submit',function(){
    var html=area.innerHTML.replace(/src="\.\.\/(uploads|images)\//g,'src="$1/');
    bodyField.value=html;
  });
})();
</script>
<?php admin_footer(); ?>
