<?php
/* Diagnostic d'import d'images — OUTIL TEMPORAIRE.
   Étape 1 : état du serveur (dossiers, droits, GD, limites).
   Étape 2 : test d'import RÉEL vers upload.php, avec affichage de la réponse
             BRUTE (code HTTP + corps), ce que l'interface normale masque.
   Protégé par require_login(). À SUPPRIMER une fois le problème résolu. */
require_once __DIR__ . '/auth.php';
require_login();

$csrf = csrf_token();
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Diagnostic import</title>
<style>
  body{font:14px/1.6 -apple-system,Segoe UI,Arial,sans-serif;background:#14171a;color:#dfe3e6;margin:0;padding:22px;}
  h1{font-size:19px;margin:0 0 18px;} h2{font-size:15px;margin:26px 0 8px;color:#8fd39a;}
  pre{background:#0d1013;border:1px solid #2a3138;border-radius:6px;padding:12px;white-space:pre-wrap;
      word-break:break-word;font:12.5px/1.55 Consolas,monospace;color:#cfd6dc;max-height:340px;overflow:auto;}
  .ok{color:#7ddc8f;} .ko{color:#ff8a7a;font-weight:700;}
  input[type=file]{margin:8px 0;} button{background:#2f6f4f;color:#fff;border:0;border-radius:6px;
      padding:9px 16px;font-size:14px;cursor:pointer;} button:hover{background:#3a8a62;}
  table{border-collapse:collapse;} td{padding:2px 14px 2px 0;vertical-align:top;}
</style>
</head>
<body>
<h1>Diagnostic d'import d'images</h1>

<h2>1. État du serveur</h2>
<table>
<?php
function l($k, $v) { echo '<tr><td>' . htmlspecialchars($k) . '</td><td>' . $v . "</td></tr>\n"; }
function b($x)     { return $x ? '<span class="ok">OUI</span>' : '<span class="ko">NON</span>'; }
l('UPLOAD_DIR', '<code>' . htmlspecialchars(UPLOAD_DIR) . '</code>');
l('existe / écriture', b(is_dir(UPLOAD_DIR)) . ' / ' . b(is_dir(UPLOAD_DIR) && is_writable(UPLOAD_DIR)));
l('fichiers présents', (string)max(0, count(@scandir(UPLOAD_DIR) ?: array()) - 2));
l('GD complet', b(function_exists('imagecreatetruecolor') && function_exists('imagejpeg')));
l('upload_max_filesize / post_max_size', htmlspecialchars(ini_get('upload_max_filesize') . ' / ' . ini_get('post_max_size')));
l('session active', b(session_status() === PHP_SESSION_ACTIVE));
l('jeton CSRF en session', b(!empty($_SESSION['csrf'])) . ' <code>' . htmlspecialchars(substr($csrf, 0, 8)) . '…</code>');
?>
</table>

<h2>2. Test d'import réel</h2>
<p>Choisis une image (la même que celle qui échoue) puis clique sur Tester.
   La réponse exacte du serveur s'affichera ci-dessous.</p>
<input type="file" id="f" accept="image/*">
<button id="go">Tester l'import</button>
<pre id="out">En attente…</pre>

<script>
var CSRF = <?= json_encode($csrf) ?>;
document.getElementById('go').addEventListener('click', function () {
  var f = document.getElementById('f').files[0];
  var out = document.getElementById('out');
  if (!f) { out.textContent = 'Choisis d\'abord une image.'; return; }

  out.textContent = 'Envoi en cours…\n\nFichier : ' + f.name + '\nType : ' + (f.type || '(inconnu)') +
                    '\nTaille : ' + Math.round(f.size / 1024) + ' Ko\n';

  var fd = new FormData();
  fd.append('csrf', CSRF);
  fd.append('file', f);

  fetch('upload.php', { method: 'POST', body: fd })
    .then(function (r) {
      return r.text().then(function (t) {
        var h = [];
        r.headers.forEach(function (v, k) { h.push('  ' + k + ': ' + v); });
        return { status: r.status, statusText: r.statusText, headers: h.join('\n'), body: t };
      });
    })
    .then(function (res) {
      out.textContent =
        'CODE HTTP : ' + res.status + ' ' + res.statusText + '\n\n' +
        'EN-TÊTES :\n' + res.headers + '\n\n' +
        'RÉPONSE BRUTE :\n' + (res.body || '(vide)') + '\n\n' +
        '--- interprétation ---\n' + interprete(res);
    })
    .catch(function (e) {
      out.textContent = 'LA REQUÊTE N\'EST MÊME PAS ARRIVÉE AU SERVEUR.\n\n' +
        'Erreur : ' + e + '\n\n' +
        'Cause probable : pare-feu, antivirus, extension de navigateur ou réseau ' +
        'qui bloque l\'envoi de fichiers.';
    });
});

function interprete(res) {
  var j = null; try { j = JSON.parse(res.body); } catch (e) {}
  if (j && j.url)   return 'IMPORT RÉUSSI : ' + j.url;
  if (j && j.error) return 'upload.php a refusé, motif : « ' + j.error + ' »';
  if (res.status === 403) return 'Refus 403 SANS réponse JSON : le blocage vient du serveur ou d\'un pare-feu, pas du CMS.';
  if (res.status >= 500)  return 'Erreur serveur : PHP a planté pendant le traitement.';
  return 'Réponse inattendue — copie ce bloc entier.';
}
</script>
</body>
</html>
