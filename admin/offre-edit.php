<?php
/* Éditeur d'offre d'emploi — calqué sur admin/billet-edit.php (éditeur de billet).
   Même mécanique (slug stable, publié/brouillon, éditeur de corps contenteditable
   synchronisé dans un champ caché, aperçu en direct via _live_preview.php), MAIS
   sans image de couverture : une offre n'a pas de photo. Champs propres à l'offre :
   contrat (liste fermée), temps, lieu, date limite de candidature, contact. */
require_once __DIR__ . '/auth.php';
require_login();

$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']) : '';
$a = $slug ? find_emploi($slug) : null;
$isNew = !$a;

$d = array(
  'slug'        => $a['slug']        ?? '',
  'title'       => $a['title']       ?? '',
  'contrat'     => emploi_contrat_key($a ?? array()),
  'temps'       => $a['temps']       ?? '',
  'lieu'        => $a['lieu']        ?? ($isNew ? 'Grenoble' : ''),
  'date'        => $a['date']        ?? date('Y-m-d'),
  'date_limite' => emploi_deadline($a ?? array()),
  'excerpt'     => $a['excerpt']     ?? '',
  'body'        => $a['body']        ?? '',
  'contact'     => $a['contact']     ?? '',
  'published'   => $a['published']   ?? true,
);

// Dans l'éditeur (sous /admin/), les images du corps pointent un cran plus haut.
$editorBody = str_replace(array('src="uploads/', 'src="images/'), array('src="../uploads/', 'src="../images/'), $d['body']);

admin_header($isNew ? 'Nouvelle offre' : "Modifier l'offre");
?>
<div class="ahead">
  <h1 class="atitle"><?= $isNew ? 'Nouvelle offre' : "Modifier l'offre" ?></h1>
  <a class="alink" href="emplois.php">← Retour</a>
</div>

<?php if (!$isNew && emploi_expired($d)): ?>
  <div class="acard aempty" style="text-align:left;">
    La date limite de candidature est <strong>dépassée</strong> : cette offre n'apparaît plus sur le site public.
    Repoussez la date limite (ou videz-la) pour la remettre en ligne.
  </div>
<?php endif; ?>

<form class="acard aform" method="post" action="offre-save.php" id="offreForm">
  <?= csrf_field() ?>
  <input type="hidden" name="orig_slug" value="<?= e($d['slug']) ?>" />

  <label class="afield">Intitulé du poste
    <input type="text" name="title" value="<?= e($d['title']) ?>" required placeholder="Ex. : Animateur·rice jeunesse" />
  </label>

  <div class="agrid2">
    <label class="afield">Type de contrat
      <select name="contrat" id="contratSel">
        <option value="">— À préciser —</option>
        <?php foreach (emploi_contrats() as $k => $label): ?>
          <option value="<?= e($k) ?>" <?= $d['contrat'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="afield">Temps de travail <span class="ahint">(texte libre)</span>
      <input type="text" name="temps" value="<?= e($d['temps']) ?>" maxlength="80" placeholder="Ex. : Temps plein, 24 h/semaine" />
    </label>
  </div>

  <div class="agrid2">
    <label class="afield">Lieu
      <input type="text" name="lieu" value="<?= e($d['lieu']) ?>" maxlength="120" placeholder="Ex. : Grenoble" />
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
    <label class="afield">Date de publication
      <input type="date" name="date" value="<?= e($d['date']) ?>" required />
    </label>
    <label class="afield">Date limite de candidature <span class="ahint">(facultative — passée cette date, l'offre disparaît du site)</span>
      <input type="date" name="date_limite" value="<?= e($d['date_limite']) ?>" />
    </label>
  </div>

  <label class="afield">Accroche courte <span class="ahint">(résumé affiché dans la liste — 1 phrase)</span>
    <input type="text" name="excerpt" value="<?= e($d['excerpt']) ?>" maxlength="200" placeholder="Une phrase qui résume le poste." />
  </label>

  <div class="afield">Description du poste
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
    <span class="ahint">Missions, profil recherché, conditions… Sélectionnez du texte puis cliquez sur un bouton pour le mettre en forme.</span>
  </div>

  <label class="afield">Comment postuler <span class="ahint">(email ou instructions — affiché dans un encart en bas de l'offre)</span>
    <textarea name="contact" rows="2" maxlength="400" placeholder="Ex. : CV et lettre de motivation à ulmjc.gre@free.fr avant le 30 septembre."><?= e($d['contact']) ?></textarea>
  </label>

  <div class="aactions">
    <button class="abtn" type="submit" id="saveBtn">Enregistrer</button>
    <a class="alink" href="emplois.php">Annuler</a>
  </div>
</form>

<script>
(function(){
  var form=document.getElementById('offreForm');
  var area=document.getElementById('editorArea');
  var bodyField=document.getElementById('bodyField');
  var toolbar=document.getElementById('editorToolbar');

  // Rafraîchit l'aperçu en direct (panneau global _live_preview.php). Poser une
  // classe (alignement) ne déclenche pas « input » → on le déclenche ici.
  function refreshPreview(){ if(window.ulmjcPreviewRefresh) window.ulmjcPreviewRefresh(); }

  // Les boutons de la barre ne doivent pas voler le focus : la sélection dans
  // l'éditeur reste intacte → l'alignement s'applique au bloc sélectionné.
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

  // À l'envoi : recopie le HTML de l'éditeur (chemins ramenés en « uploads/ » /
  // « images/ ») dans le champ caché envoyé au serveur.
  form.addEventListener('submit',function(){
    var html=area.innerHTML.replace(/src="\.\.\/(uploads|images)\//g,'src="$1/');
    bodyField.value=html;
  });
})();
</script>
<?php admin_footer(); ?>
