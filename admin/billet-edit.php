<?php
/* Éditeur de billet de blog — basé sur admin/edit.php (éditeur d'actualité).
   Identique à l'éditeur d'actu (couverture filtre/effet/taille, corps avec
   alignement, chapô, accroche, publié) + deux champs propres au blog :
   AUTEUR (texte libre) et CATÉGORIE (thème, choisi dans blog_categories()). */
require_once __DIR__ . '/auth.php';
require_login();

$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']) : '';
$a = $slug ? find_blog($slug) : null;
$isNew = !$a;

$d = array(
  'slug'      => $a['slug']      ?? '',
  'title'     => $a['title']     ?? '',
  'date'      => $a['date']      ?? date('Y-m-d'),
  'author'    => $a['author']    ?? '',
  'category'  => blog_category_key($a ?? array()),
  'excerpt'   => $a['excerpt']   ?? '',
  'chapo'     => $a['chapo']     ?? '',
  'cover'     => $a['cover']     ?? '',
  'filter'    => $a['filter']    ?? 'naturel',
  'effect'    => $a['effect']    ?? 'kenburns',
  'cover_w'   => isset($a['cover_w']) ? max(40, min(100, (int)$a['cover_w'])) : 100,
  'cover_align' => !empty($a['cover_align']),
  'body'      => $a['body']      ?? '',
  'published' => $a['published'] ?? true,
);

// Dans l'éditeur (sous /admin/), les images doivent pointer un cran plus haut.
$editorBody = str_replace(array('src="uploads/', 'src="images/'), array('src="../uploads/', 'src="../images/'), $d['body']);

admin_header($isNew ? 'Nouveau billet' : 'Modifier le billet');
?>
<div class="ahead">
  <h1 class="atitle"><?= $isNew ? 'Nouveau billet' : 'Modifier le billet' ?></h1>
  <a class="alink" href="blog.php">← Retour</a>
</div>

<form class="acard aform" method="post" action="billet-save.php" enctype="multipart/form-data" id="actuForm">
  <?= csrf_field() ?>
  <input type="hidden" name="orig_slug" value="<?= e($d['slug']) ?>" />

  <label class="afield">Titre
    <input type="text" name="title" value="<?= e($d['title']) ?>" required placeholder="Ex. : Une semaine au chalet avec les jeunes" />
  </label>

  <div class="agrid2">
    <label class="afield">Date
      <input type="date" name="date" value="<?= e($d['date']) ?>" required />
    </label>
    <label class="afield aswitch-field">Statut
      <label class="aswitch">
        <input type="checkbox" name="published" value="1" <?= $d['published'] ? 'checked' : '' ?> />
        <span class="aswitch-track"><span class="aswitch-thumb"></span></span>
        <span class="aswitch-lbl">Publié (visible sur le site)</span>
      </label>
    </label>
  </div>

  <div class="agrid2">
    <label class="afield">Auteur <span class="ahint">(nom affiché « Par … »)</span>
      <input type="text" name="author" value="<?= e($d['author']) ?>" maxlength="120" placeholder="Ex. : Le bureau de l'union" />
    </label>
    <label class="afield">Thème
      <select name="category" id="categorySel">
        <option value="">— Sans thème —</option>
        <?php foreach (blog_categories() as $k => $label): ?>
          <option value="<?= e($k) ?>" <?= $d['category'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>

  <label class="afield">Accroche courte <span class="ahint">(résumé affiché dans la liste — 1 phrase)</span>
    <input type="text" name="excerpt" value="<?= e($d['excerpt']) ?>" maxlength="200" placeholder="Une phrase qui donne envie de lire." />
  </label>

  <label class="afield">Chapô <span class="ahint">(introduction en italique en haut du billet — facultatif)</span>
    <textarea name="chapo" rows="2" placeholder="L'idée forte du billet, en une ou deux phrases."><?= e($d['chapo']) ?></textarea>
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

  <!-- Filtre / effet / taille de la couverture (aperçu en direct sous le titre) -->
  <div class="afield cover-w-field" id="coverWField"<?= $d['cover'] ? '' : ' hidden' ?>>
    <span class="cover-w-lbl">Taille de la couverture <span class="pop-width-val" id="coverWVal"><?= (int)$d['cover_w'] ?>%</span></span>
    <span class="acover-stage" id="coverStage" aria-hidden="true">
      <span class="acover-doc" id="coverDoc">
        <span class="acover-kicker" id="coverPrevKicker">Blog</span>
        <span class="acover-title" id="coverPrevTitle"><?= $d['title'] !== '' ? e($d['title']) : 'Titre du billet' ?></span>
        <span class="acover-prev<?= effect_class($d) ?>" id="coverPrev" style="<?= $d['cover'] ? cover_style($d, '../') : '' ?>"></span>
      </span>
    </span>
    <label class="cover-align"><input type="checkbox" name="cover_align" id="coverAlign" value="1"<?= $d['cover_align'] ? ' checked' : '' ?> /> Aligner sur la largeur du titre</label>
    <span class="cover-w-row" id="coverWRow"<?= $d['cover_align'] ? ' hidden' : '' ?>>
      <input type="range" name="cover_w" id="coverW" min="40" max="100" step="5" value="<?= (int)$d['cover_w'] ?>" />
    </span>
    <span class="ahint">Aperçu de la couverture sous le titre, à l'échelle de la page.</span>
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

  <div class="afield">Corps du billet
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
    <a class="alink" href="blog.php">Annuler</a>
  </div>
</form>

<script>
(function(){
  var form=document.getElementById('actuForm');
  var area=document.getElementById('editorArea');
  var bodyField=document.getElementById('bodyField');
  var toolbar=document.getElementById('editorToolbar');

  // Rafraîchit l'aperçu en direct (panneau global _live_preview.php). Poser une
  // classe (alignement) ne déclenche pas « input » → on le déclenche ici.
  function refreshPreview(){ if(window.ulmjcPreviewRefresh) window.ulmjcPreviewRefresh(); else if(window.miPreviewRefresh) window.miPreviewRefresh(); }

  // Les boutons de la barre ne doivent pas voler le focus : la sélection dans
  // l'éditeur reste intacte → l'alignement s'applique au bloc sélectionné.
  toolbar.addEventListener('mousedown',function(e){ if(e.target.closest('button')) e.preventDefault(); });

  // Barre d'outils : execCommand simple (contenteditable). Suffisant pour des
  // bénévoles ; le corps est de toute façon re-nettoyé côté serveur (sanitize_body).
  toolbar.addEventListener('click',function(e){
    var alignBtn=e.target.closest('button[data-align]');
    if(alignBtn){
      e.preventDefault(); area.focus();
      applyAlign(alignBtn.getAttribute('data-align'));
      refreshPreview(); // changer une classe n'émet pas « input »
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

  // Alignement : pose la classe voulue sur le bloc contenant la sélection
  // (gauche = aucune classe = défaut). center→al-center, right→al-right, justify→just.
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

  /* ---- Aperçu en direct de la couverture : filtre + effet + taille ---- */
  // Palette de filtres JS = miroir de cover_filters() (inc/lib.php). Adapter les
  // deux ensemble. « layers » sont empilés avant l'image, « blend » = blend-mode.
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
  // Couverture initiale (image déjà enregistrée) : chemin ../ pour l'affichage sous /admin/.
  var coverUrl=<?= json_encode($d['cover'] ? '../' . e($d['cover']) : '') ?>;

  // Réduit le « doc » construit à l'échelle réelle (1040 px) pour tenir dans le cadre.
  function fitStage(){
    if(!coverDoc||!coverStage) return;
    var w=coverStage.clientWidth; if(!w) return;
    var s=w/coverDoc.offsetWidth; // offsetWidth ≈ 1040
    coverDoc.style.transform='scale('+s+')';
    coverStage.style.height=Math.ceil(coverDoc.offsetHeight*s)+'px';
  }
  // Cale la couverture sur la largeur RÉELLE du titre (convertie à l'échelle réelle du doc).
  function alignCoverToTitle(){
    if(!cp||!coverPrevTitle||!coverDoc||!coverStage||!coverStage.clientWidth) return;
    var r=document.createRange(); r.selectNodeContents(coverPrevTitle);
    var realW=r.getBoundingClientRect().width*coverDoc.offsetWidth/coverStage.clientWidth;
    if(realW>0) cp.style.width=Math.round(realW)+'px';
  }
  function applyCoverWidth(){
    if(!cp) return;
    if(coverAlign&&coverAlign.checked){ fitStage(); alignCoverToTitle(); if(coverWVal) coverWVal.textContent='largeur du titre'; }
    else if(coverW){ cp.style.width=coverW.value+'%'; if(coverWVal) coverWVal.textContent=coverW.value+'%'; }
    fitStage();
  }
  // Ratio du bandeau d'après la vraie photo : portrait → ratio naturel ; paysage → 16:9.
  function applyCoverRatio(){
    if(!cp||!coverUrl) return;
    var img=new Image();
    img.onload=function(){ var r=img.naturalHeight?img.naturalWidth/img.naturalHeight:16/9; cp.style.aspectRatio=(r<1?r:16/9)+' / 1'; fitStage(); };
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
  if(titleInput) titleInput.addEventListener('input',function(){ if(coverPrevTitle) coverPrevTitle.textContent=titleInput.value.trim()||'Titre du billet'; applyCoverWidth(); });
  if(coverAlign) coverAlign.addEventListener('change',function(){ if(coverWRow) coverWRow.hidden=this.checked; applyCoverWidth(); refreshPreview(); });
  if(filterSel) filterSel.addEventListener('change',function(){ renderCoverPrev(); refreshPreview(); });
  if(effectSel) effectSel.addEventListener('change',function(){ renderCoverPrev(); refreshPreview(); });
  if(coverW) coverW.addEventListener('input',applyCoverWidth);
  if(coverW) coverW.addEventListener('change',refreshPreview);
  window.addEventListener('resize',applyCoverWidth);
  renderCoverPrev();
  if(document.fonts&&document.fonts.ready) document.fonts.ready.then(applyCoverWidth);

  // À l'envoi : recopie le HTML de l'éditeur (chemins ramenés en « uploads/ » /
  // « images/ ») dans le champ caché envoyé au serveur.
  form.addEventListener('submit',function(){
    var html=area.innerHTML.replace(/src="\.\.\/(uploads|images)\//g,'src="$1/');
    bodyField.value=html;
  });
})();
</script>
<?php admin_footer(); ?>
