<?php
/* Éditeur d'activité — calqué sur admin/edit.php (actualités).
   Champs adaptés : titre, image (upload OU médiathèque, même moule que la couverture
   d'actu), jour, horaire, public, ordre, publié/brouillon, description (éditeur
   visuel simple + insertion d'images, re-nettoyée côté serveur par sanitize_body). */
require_once __DIR__ . '/auth.php';
require_login();

$slug  = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']) : '';
$a     = $slug ? find_activite($slug) : null;
$isNew = !$a;

$d = array(
  'slug'        => $a['slug']        ?? '',
  'title'       => $a['title']       ?? '',
  'image'       => $a['image']       ?? '',
  'jour'        => $a['jour']        ?? '',
  'horaire'     => $a['horaire']     ?? '',
  'public'      => $a['public']      ?? '',
  'ordre'       => $a['ordre']       ?? 0,
  'description' => $a['description'] ?? '',
  'published'   => $a['published']   ?? true,
);

// Dans l'éditeur (sous /admin/), les images doivent pointer un cran plus haut.
$editorBody = str_replace(array('src="uploads/', 'src="images/'), array('src="../uploads/', 'src="../images/'), $d['description']);

admin_header($isNew ? 'Nouvelle activité' : 'Modifier l\'activité');
?>
<div class="ahead">
  <h1 class="atitle"><?= $isNew ? 'Nouvelle activité' : 'Modifier l\'activité' ?></h1>
  <a class="alink" href="activites.php">← Retour</a>
</div>

<form class="acard aform" method="post" action="activite-save.php" enctype="multipart/form-data" id="actuForm">
  <?= csrf_field() ?>
  <input type="hidden" name="orig_slug" value="<?= e($d['slug']) ?>" />

  <label class="afield">Titre
    <input type="text" name="title" value="<?= e($d['title']) ?>" required placeholder="Ex. : Ski alpin" />
  </label>

  <div class="agrid2">
    <label class="afield">Ordre d'affichage <span class="ahint">(plus petit = affiché en premier)</span>
      <input type="number" name="ordre" value="<?= e((string)$d['ordre']) ?>" step="1" />
    </label>
    <label class="afield aswitch-field">Statut
      <label class="aswitch">
        <input type="checkbox" name="published" value="1" <?= $d['published'] ? 'checked' : '' ?> />
        <span class="aswitch-track"><span class="aswitch-thumb"></span></span>
        <span class="aswitch-lbl">Publiée (visible sur le site)</span>
      </label>
    </label>
  </div>

  <div class="agrid2">
    <label class="afield">Saison / jour <span class="ahint">(ex. : Hiver, Toute l'année)</span>
      <input type="text" name="jour" value="<?= e($d['jour']) ?>" placeholder="Hiver" />
    </label>
    <label class="afield">Horaire <span class="ahint">(facultatif)</span>
      <input type="text" name="horaire" value="<?= e($d['horaire']) ?>" placeholder="En journée" />
    </label>
  </div>

  <label class="afield">Public <span class="ahint">(ex. : Tous niveaux, Famille)</span>
    <input type="text" name="public" value="<?= e($d['public']) ?>" placeholder="Tous niveaux" />
  </label>

  <div class="afield">Image <span class="ahint">(facultatif)</span>
    <input type="hidden" name="cover" id="coverField" value="<?= e($d['image']) ?>" />
    <input type="hidden" name="cover_remove" id="coverRemoveFlag" value="" />
    <div class="cover-preview" id="coverPreview"<?= $d['image'] ? '' : ' hidden' ?>>
      <img id="coverImg" src="<?= $d['image'] ? e('../' . $d['image']) : '' ?>" alt="" />
    </div>
    <div class="hero-upload-row" style="margin-top:8px">
      <label class="abtn abtn-ghost">Importer une photo
        <input type="file" name="cover_file" accept="image/*" id="coverInput" />
      </label>
      <button type="button" class="abtn abtn-ghost" id="coverPickBtn">Choisir dans la médiathèque</button>
      <button type="button" class="alink adanger" id="coverRemoveBtn"<?= $d['image'] ? '' : ' hidden' ?>>Retirer la photo</button>
    </div>
    <span class="secphoto-fname" id="coverFileName"></span>
    <span class="ahint">JPG ou PNG. Redimensionnée automatiquement à l'enregistrement.</span>
  </div>

  <div class="afield">Description
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
    <a class="alink" href="activites.php">Annuler</a>
  </div>
</form>

<script>
(function(){
  var form=document.getElementById('actuForm');
  var area=document.getElementById('editorArea');
  var bodyField=document.getElementById('bodyField');
  var toolbar=document.getElementById('editorToolbar');

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

  form.addEventListener('submit',function(){
    var html=area.innerHTML.replace(/src="\.\.\/(uploads|images)\//g,'src="$1/');
    bodyField.value=html;
  });
})();
</script>
<?php admin_footer(); ?>
