<?php
/* Back-office ULMJC : Photos du chalet (galerie par catégories).
   Écran hybride : une section par catégorie fixe (Extérieur, Couchage, Sanitaires,
   Cuisine, Pièces de vie). Pour chaque catégorie : vignettes des photos actuelles,
   réordonnancement (monter/descendre), retrait, et ajout (import → optimize_image →
   uploads/ hors dépôt, OU choix depuis la médiathèque via openMediaPicker).
   Persistance IMMÉDIATE via chalet-save.php + save_gallery() (inspiré de
   section-media.php du CMS mohamed). CSRF sur toutes les écritures. */
require_once __DIR__ . '/auth.php';
require_login();

$gallery = load_gallery();
$cats    = chalet_categories();

admin_header('Photos du chalet');
?>
<div class="ahead">
  <div>
    <h1 class="atitle">Photos du chalet</h1>
    <p class="asub">Gérez les photos de chaque catégorie. Les changements sont enregistrés immédiatement.</p>
  </div>
  <div class="ahead-actions">
    <a class="abtn abtn-ghost" href="../chalet.php" target="ulmjc_site">Voir la page Chalet ↗</a>
  </div>
</div>

<div class="chalet-admin">
  <?php foreach ($cats as $key => $meta):
    $photos = isset($gallery[$key]) ? $gallery[$key] : array(); ?>
  <section class="acard chalet-cat" data-cat="<?= e($key) ?>">
    <div class="chalet-cat-head">
      <h2><span class="chalet-cat-icon"><?= $meta['icon'] ?></span> <?= e($meta['label']) ?> <span class="ahint chalet-count">(<?= count($photos) ?>)</span></h2>
      <div class="chalet-cat-actions">
        <label class="abtn abtn-ghost">Importer des photos
          <input type="file" class="chalet-upload" accept="image/jpeg,image/png,image/webp" multiple />
        </label>
        <button type="button" class="abtn abtn-ghost chalet-pick">Choisir dans la médiathèque</button>
        <span class="ahint chalet-progress" aria-live="polite"></span>
      </div>
    </div>
    <div class="chalet-grid" data-cat="<?= e($key) ?>">
      <?php foreach ($photos as $src): ?>
        <div class="chalet-ph" data-src="<?= e($src) ?>">
          <span class="chalet-ph-img" style="background-image:url('<?= e('../' . $src) ?>')"></span>
          <div class="chalet-ph-tools">
            <button type="button" class="chalet-mv chalet-mv-up" title="Monter" aria-label="Monter">↑</button>
            <button type="button" class="chalet-mv chalet-mv-down" title="Descendre" aria-label="Descendre">↓</button>
            <button type="button" class="chalet-rm" title="Retirer" aria-label="Retirer">×</button>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$photos): ?><p class="chalet-empty mp-empty">Aucune photo dans cette catégorie.</p><?php endif; ?>
    </div>
  </section>
  <?php endforeach; ?>
</div>

<style>
/* Écran galerie chalet (reprend la palette admin.css) */
.chalet-admin{display:flex;flex-direction:column;gap:1.4rem;}
.chalet-cat-head{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;}
.chalet-cat-head h2{font-size:1.35rem;margin:0;}
.chalet-cat-icon{font-family:var(--sans);}
.chalet-cat-actions{display:flex;gap:.6rem;flex-wrap:wrap;}
.chalet-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;}
.chalet-ph{position:relative;border:1px solid var(--taupe);border-radius:6px;overflow:hidden;background:#fff;}
.chalet-ph-img{display:block;width:100%;aspect-ratio:4/3;background:#fff center/cover no-repeat;}
.chalet-ph-tools{position:absolute;inset:auto 0 0 0;display:flex;justify-content:center;gap:4px;padding:5px;background:linear-gradient(transparent,rgba(26,24,18,.55));}
.chalet-ph-tools button{width:26px;height:26px;border:none;border-radius:50%;background:rgba(26,24,18,.78);color:#fff;font-size:15px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;}
.chalet-ph-tools button:hover{background:var(--terra-dark);}
.chalet-rm:hover{background:#b33;}
.chalet-empty{grid-column:1/-1;}
.chalet-cat.is-busy{opacity:.6;pointer-events:none;}
</style>

<script>
(function(){
  function post(fd){
    // __CSRF est injecté par admin_footer() (plus bas dans la page) : on le lit au
    // moment de l'appel (dans un callback), jamais au chargement du script.
    fd.append('csrf', window.__CSRF || '');
    return fetch('chalet-save.php',{method:'POST',body:fd}).then(function(r){return r.json();});
  }

  // (Re)construit la grille d'une catégorie à partir de la liste de chemins renvoyée.
  function render(grid, items){
    grid.innerHTML='';
    if(!items.length){
      var p=document.createElement('p'); p.className='chalet-empty mp-empty';
      p.textContent='Aucune photo dans cette catégorie.'; grid.appendChild(p);
    } else {
      items.forEach(function(src){
        var cell=document.createElement('div'); cell.className='chalet-ph'; cell.setAttribute('data-src',src);
        var img=document.createElement('span'); img.className='chalet-ph-img'; img.style.backgroundImage="url('../"+src+"')";
        var tools=document.createElement('div'); tools.className='chalet-ph-tools';
        tools.innerHTML='<button type="button" class="chalet-mv chalet-mv-up" title="Monter" aria-label="Monter">↑</button>'
                       +'<button type="button" class="chalet-mv chalet-mv-down" title="Descendre" aria-label="Descendre">↓</button>'
                       +'<button type="button" class="chalet-rm" title="Retirer" aria-label="Retirer">×</button>';
        cell.appendChild(img); cell.appendChild(tools); grid.appendChild(cell);
      });
    }
    // Compteur dans le titre
    var sec=grid.closest('.chalet-cat');
    var cnt=sec&&sec.querySelector('.chalet-count'); if(cnt) cnt.textContent='('+items.length+')';
  }

  function currentOrder(grid){
    return Array.prototype.map.call(grid.querySelectorAll('.chalet-ph'),function(c){return c.getAttribute('data-src');});
  }

  document.querySelectorAll('.chalet-cat').forEach(function(sec){
    var cat=sec.getAttribute('data-cat');
    var grid=sec.querySelector('.chalet-grid');
    var upInput=sec.querySelector('.chalet-upload');
    var pickBtn=sec.querySelector('.chalet-pick');

    function busy(on){ sec.classList.toggle('is-busy', !!on); }

    // Import fichier → upload + ajout
    upInput.addEventListener('change',function(){
      var files=upInput.files?Array.prototype.slice.call(upInput.files):[]; if(!files.length)return;
      var total=files.length, errors=0, lastItems=null;
      busy(true);
      var lbl=sec.querySelector('.chalet-progress');
      (function step(i){
        if(i>=total){ upInput.value=''; busy(false); if(lbl)lbl.textContent=''; if(lastItems)render(grid,lastItems); if(errors)alert(errors+' photo(s) sur '+total+' n\'ont pas pu être importées.'); return; }
        if(lbl)lbl.textContent='Import… '+(i+1)+'/'+total;
        var fd=new FormData(); fd.append('action','upload'); fd.append('cat',cat); fd.append('file',files[i]);
        post(fd).then(function(j){ if(j&&j.error){errors++;} else if(j&&j.items){lastItems=j.items;} step(i+1); })
                .catch(function(){ errors++; step(i+1); });
      })(0);
    });

    // Médiathèque → ajout (multiple)
    pickBtn.addEventListener('click',function(){
      if(!window.openMediaPicker){ alert('Médiathèque indisponible.'); return; }
      window.openMediaPicker(function(picked){
        var srcs=Array.isArray(picked)?picked:[picked]; if(!srcs.length)return;
        busy(true);
        var fd=new FormData(); fd.append('action','select'); fd.append('cat',cat);
        srcs.forEach(function(s){ fd.append('src[]',s); });
        post(fd).then(function(j){ busy(false); if(j.error){alert(j.error);return;} render(grid,j.items||[]); })
                .catch(function(){ busy(false); alert('Ajout impossible.'); });
      },{multiple:true});
    });

    // Retirer / monter / descendre (délégation)
    grid.addEventListener('click',function(e){
      var cell=e.target.closest('.chalet-ph'); if(!cell)return;
      var src=cell.getAttribute('data-src');
      if(e.target.closest('.chalet-rm')){
        if(!confirm("Retirer cette photo de la catégorie ? (le fichier n'est pas supprimé)")) return;
        busy(true);
        var fd=new FormData(); fd.append('action','remove'); fd.append('cat',cat); fd.append('src',src);
        post(fd).then(function(j){ busy(false); if(j.error){alert(j.error);return;} render(grid,j.items||[]); })
                .catch(function(){ busy(false); alert('Retrait impossible.'); });
        return;
      }
      var dir=0;
      if(e.target.closest('.chalet-mv-up')) dir=-1;
      else if(e.target.closest('.chalet-mv-down')) dir=1;
      if(dir!==0){
        // Réordonne localement puis persiste le nouvel ordre complet.
        var order=currentOrder(grid);
        var i=order.indexOf(src); var j2=i+dir;
        if(i<0||j2<0||j2>=order.length) return;
        order.splice(j2,0,order.splice(i,1)[0]);
        render(grid,order); // maj optimiste
        busy(true);
        var fd=new FormData(); fd.append('action','reorder'); fd.append('cat',cat);
        order.forEach(function(s){ fd.append('order[]',s); });
        post(fd).then(function(j){ busy(false); if(j.error){alert(j.error);return;} render(grid,j.items||[]); })
                .catch(function(){ busy(false); alert('Réordonnancement impossible.'); });
      }
    });
  });
})();
</script>
<?php admin_footer(); ?>
