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
  'filter'      => $a['filter']      ?? 'naturel',
  'effect'      => $a['effect']      ?? 'kenburns',
  'cover_w'     => isset($a['cover_w']) ? max(40, min(100, (int)$a['cover_w'])) : 100,
  'cover_align' => !empty($a['cover_align']),
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

  <!-- Filtre / effet / taille de l'image (aperçu en direct sous le titre) -->
  <div class="afield cover-w-field" id="coverWField"<?= $d['image'] ? '' : ' hidden' ?>>
    <span class="cover-w-lbl">Taille de l'image <span class="pop-width-val" id="coverWVal"><?= (int)$d['cover_w'] ?>%</span></span>
    <span class="acover-stage" id="coverStage" aria-hidden="true">
      <span class="acover-doc" id="coverDoc">
        <span class="acover-kicker" id="coverPrevKicker">Activité</span>
        <span class="acover-title" id="coverPrevTitle"><?= $d['title'] !== '' ? e($d['title']) : 'Titre de l\'activité' ?></span>
        <span class="acover-prev<?= effect_class($d) ?>" id="coverPrev" style="<?= $d['image'] ? cover_style($d, '../') : '' ?>"></span>
      </span>
    </span>
    <label class="cover-align"><input type="checkbox" name="cover_align" id="coverAlign" value="1"<?= $d['cover_align'] ? ' checked' : '' ?> /> Aligner sur la largeur du texte</label>
    <span class="cover-w-row" id="coverWRow"<?= $d['cover_align'] ? ' hidden' : '' ?>>
      <input type="range" name="cover_w" id="coverW" min="40" max="100" step="5" value="<?= (int)$d['cover_w'] ?>" />
    </span>
    <span class="ahint">Aperçu de l'image sous le titre, à l'échelle de la page.</span>
  </div>

  <div class="agrid2">
    <label class="afield">Filtre photo
      <select name="filter" id="filterSel">
        <?php foreach (cover_filters() as $k => $f): ?>
          <option value="<?= e($k) ?>" <?= $d['filter'] === $k ? 'selected' : '' ?>><?= e($f['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="afield">Effet
      <select name="effect" id="effectSel">
        <option value="kenburns" <?= $d['effect'] === 'kenburns' ? 'selected' : '' ?>>Zoom + panoramique (Ken Burns)</option>
        <option value="zoom" <?= $d['effect'] === 'zoom' ? 'selected' : '' ?>>Zoom avant</option>
        <option value="pano" <?= $d['effect'] === 'pano' ? 'selected' : '' ?>>Panoramique</option>
        <option value="fixe" <?= $d['effect'] === 'fixe' ? 'selected' : '' ?>>Fixe</option>
      </select>
    </label>
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
      <span class="editor-sep"></span>
      <button type="button" class="ed-ico" data-align="left" title="Aligner à gauche (défaut)"><svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><g fill="currentColor"><rect x="1" y="2" width="12" height="1.4"/><rect x="1" y="6.3" width="8" height="1.4"/><rect x="1" y="10.6" width="10" height="1.4"/></g></svg></button>
      <button type="button" class="ed-ico" data-align="center" title="Centrer"><svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><g fill="currentColor"><rect x="1" y="2" width="12" height="1.4"/><rect x="3" y="6.3" width="8" height="1.4"/><rect x="2" y="10.6" width="10" height="1.4"/></g></svg></button>
      <button type="button" class="ed-ico" data-align="right" title="Aligner à droite"><svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><g fill="currentColor"><rect x="1" y="2" width="12" height="1.4"/><rect x="5" y="6.3" width="8" height="1.4"/><rect x="3" y="10.6" width="10" height="1.4"/></g></svg></button>
      <button type="button" class="ed-ico" data-align="justify" title="Justifier"><svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><g fill="currentColor"><rect x="1" y="2" width="12" height="1.4"/><rect x="1" y="6.3" width="12" height="1.4"/><rect x="1" y="10.6" width="12" height="1.4"/></g></svg></button>
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

  // Rafraîchit l'aperçu en direct (panneau global _live_preview.php).
  function refreshPreview(){ if(window.ulmjcPreviewRefresh) window.ulmjcPreviewRefresh(); else if(window.miPreviewRefresh) window.miPreviewRefresh(); }

  // Les boutons de la barre ne volent pas le focus (la sélection du bloc reste intacte).
  toolbar.addEventListener('mousedown',function(e){ if(e.target.closest('button')) e.preventDefault(); });

  toolbar.addEventListener('click',function(e){
    var alignBtn=e.target.closest('button[data-align]');
    if(alignBtn){
      e.preventDefault(); area.focus();
      applyAlign(alignBtn.getAttribute('data-align'));
      refreshPreview();
      return;
    }
    var btn=e.target.closest('button[data-cmd]'); if(!btn)return;
    e.preventDefault(); area.focus();
    var cmd=btn.getAttribute('data-cmd'), val=btn.getAttribute('data-val')||null;
    if(cmd==='createLink'){
      var url=prompt('Adresse du lien (https://…)'); if(!url)return;
      document.execCommand('createLink',false,url); return;
    }
    document.execCommand(cmd,false,val);
  });

  // Alignement : pose la classe sur le bloc contenant la sélection (gauche = défaut).
  function applyAlign(which){
    var s=window.getSelection();
    if(!s||!s.rangeCount) return;
    var n=s.getRangeAt(0).startContainer;
    if(n.nodeType===3) n=n.parentNode;
    while(n && n!==area && n.parentNode!==area) n=n.parentNode;
    if(n && n!==area && n.nodeType===1){
      n.classList.remove('just','al-center','al-right');
      if(which==='center') n.classList.add('al-center');
      else if(which==='right') n.classList.add('al-right');
      else if(which==='justify') n.classList.add('just');
    }
  }

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
      coverUrl='../'+src; renderCoverPrev(); refreshPreview();
    });
  });
  coverInput.addEventListener('change',function(){
    var f=coverInput.files&&coverInput.files[0]; if(!f)return;
    coverField.value=''; coverRemoveFlag.value='';
    coverFileName.textContent=f.name;
    var rd=new FileReader(); rd.onload=function(){ showCover(rd.result); }; rd.readAsDataURL(f);
    coverUrl=URL.createObjectURL(f); renderCoverPrev(); refreshPreview();
  });
  coverRemoveBtn.addEventListener('click',function(){
    coverField.value=''; coverInput.value=''; coverRemoveFlag.value='1';
    coverFileName.textContent=''; coverImg.src=''; coverPreview.hidden=true; coverRemoveBtn.hidden=true;
    coverUrl=''; renderCoverPrev(); refreshPreview();
  });

  /* ---- Aperçu en direct de l'image : filtre + effet + taille ---- */
  // Palette de filtres JS = miroir de cover_filters() (inc/lib.php). Adapter ensemble.
  var FILTERS={
    naturel:{layers:"",blend:"normal",css:""},
    nb:{layers:"linear-gradient(#808080,#808080),",blend:"color,normal",css:""},
    sepia:{layers:"linear-gradient(150deg,rgba(120,82,42,.5),rgba(60,38,14,.55)),linear-gradient(#808080,#808080),",blend:"multiply,color,normal",css:""},
    vif:{layers:"",blend:"normal",css:"saturate(1.4) contrast(1.05)"},
    delave:{layers:"",blend:"normal",css:"saturate(.78) contrast(.93) brightness(1.05)"},
    duotone:{layers:"linear-gradient(150deg,rgba(196,98,58,.42),rgba(26,51,40,.55)),linear-gradient(#808080,#808080),",blend:"multiply,color,normal",css:""}
  };
  var cp=document.getElementById('coverPrev');
  var filterSel=document.getElementById('filterSel'), effectSel=document.getElementById('effectSel');
  var coverW=document.getElementById('coverW'), coverWVal=document.getElementById('coverWVal');
  var coverWField=document.getElementById('coverWField');
  var coverAlign=document.getElementById('coverAlign'), coverWRow=document.getElementById('coverWRow');
  var coverDoc=document.getElementById('coverDoc'), coverStage=document.getElementById('coverStage');
  var coverPrevTitle=document.getElementById('coverPrevTitle');
  var titleInput=document.querySelector('input[name=title]');
  var coverUrl=<?= json_encode($d['image'] ? '../' . e($d['image']) : '') ?>;

  function fitStage(){
    if(!coverDoc||!coverStage) return;
    var w=coverStage.clientWidth; if(!w) return;
    var s=w/coverDoc.offsetWidth;
    coverDoc.style.transform='scale('+s+')';
    coverStage.style.height=Math.ceil(coverDoc.offsetHeight*s)+'px';
  }
  function alignCoverToTitle(){
    if(!cp||!coverPrevTitle||!coverDoc||!coverStage||!coverStage.clientWidth) return;
    var r=document.createRange(); r.selectNodeContents(coverPrevTitle);
    var realW=r.getBoundingClientRect().width*coverDoc.offsetWidth/coverStage.clientWidth;
    if(realW>0) cp.style.width=Math.round(realW)+'px';
  }
  function applyCoverWidth(){
    if(!cp) return;
    // Fid�le � la vraie page : bandeau CENTR�, � l'�chelle r�elle du conteneur (1092px).
    // Align� = 720px (largeur de la colonne de texte) ; sinon 960�cw%.
    if(coverAlign&&coverAlign.checked){ cp.style.width='720px'; if(coverWVal) coverWVal.textContent='largeur du texte'; }
    else if(coverW){ cp.style.width=Math.round(960*coverW.value/100)+'px'; if(coverWVal) coverWVal.textContent=coverW.value+'%'; }
    fitStage();
  }
  function applyCoverRatio(){
    if(!cp||!coverUrl) return;
    var img=new Image();
    img.onload=function(){ var r=img.naturalHeight?img.naturalWidth/img.naturalHeight:16/9; cp.style.aspectRatio=r+' / 1'; fitStage(); };
    img.src=coverUrl;
  }
  function renderCoverPrev(){
    if(!cp) return;
    if(!coverUrl){ cp.style.display='none'; if(coverWField) coverWField.hidden=true; return; }
    var f=FILTERS[filterSel?filterSel.value:'naturel']||FILTERS.naturel;
    cp.style.backgroundImage=f.layers+"url('"+coverUrl+"')";
    cp.style.backgroundSize='cover';
    cp.style.backgroundPosition='center';
    cp.style.backgroundBlendMode=f.blend;
    cp.style.filter=f.css||'none';
    cp.style.display='block';
    cp.classList.remove('fx-fixe','fx-zoom','fx-pano');
    var ev=effectSel?effectSel.value:'kenburns';
    if(ev!=='kenburns') cp.classList.add('fx-'+ev);
    if(coverWField) coverWField.hidden=false;
    applyCoverWidth();
    applyCoverRatio();
  }
  if(titleInput) titleInput.addEventListener('input',function(){ if(coverPrevTitle) coverPrevTitle.textContent=titleInput.value.trim()||'Titre de l\'activité'; applyCoverWidth(); });
  if(coverAlign) coverAlign.addEventListener('change',function(){ if(coverWRow) coverWRow.hidden=this.checked; applyCoverWidth(); refreshPreview(); });
  if(filterSel) filterSel.addEventListener('change',function(){ renderCoverPrev(); refreshPreview(); });
  if(effectSel) effectSel.addEventListener('change',function(){ renderCoverPrev(); refreshPreview(); });
  if(coverW) coverW.addEventListener('input',applyCoverWidth);
  if(coverW) coverW.addEventListener('change',refreshPreview);
  window.addEventListener('resize',applyCoverWidth);
  renderCoverPrev();
  if(document.fonts&&document.fonts.ready) document.fonts.ready.then(applyCoverWidth);

  form.addEventListener('submit',function(){
    var html=area.innerHTML.replace(/src="\.\.\/(uploads|images)\//g,'src="$1/');
    bodyField.value=html;
  });
})();
</script>
<?php admin_footer(); ?>
