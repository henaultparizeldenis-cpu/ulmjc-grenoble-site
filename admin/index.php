<?php
/* Back-office ULMJC : connexion (ou création du mot de passe à la 1re utilisation)
   + liste des Actualités. Basé sur mohamed-cms/site/admin/index.php.
   Changements : drapeau de session ulmjc_admin ; libellés ULMJC ; à la première
   utilisation (aucun mot de passe défini), on force la création d'un mot de passe. */
require_once __DIR__ . '/auth.php';

$error = '';

/* --- Première utilisation : création du mot de passe (aucun défaut livré) --- */
if (needs_setup()) {
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    $new = (string)($_POST['pass'] ?? '');
    $conf = (string)($_POST['confirm'] ?? '');
    if (mb_strlen($new) < 8)      $error = 'Choisissez un mot de passe d\'au moins 8 caractères.';
    elseif ($new !== $conf)       $error = 'La confirmation ne correspond pas.';
    elseif (set_admin_pass($new)) {
      $_SESSION['ulmjc_admin'] = true;
      session_regenerate_id(true);
      $_SESSION['ulmjc_admin'] = true;
      header('Location: index.php'); exit;
    } else {
      $error = 'Impossible d\'enregistrer le mot de passe (droits du dossier ?).';
    }
  }
  admin_header('Configuration');
  ?>
  <div class="acard alogin">
    <span class="amark big">ULMJC</span>
    <h1 class="atitle">Première connexion</h1>
    <p class="asub">Créez le mot de passe qui protégera l'espace de publication. Notez-le en lieu sûr : il n'y a pas de mot de passe par défaut.</p>
    <?php if ($error): ?><p class="aerror"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
      <label class="afield">Nouveau mot de passe
        <input type="password" name="pass" autofocus required minlength="8" />
      </label>
      <label class="afield">Confirmer le mot de passe
        <input type="password" name="confirm" required minlength="8" />
      </label>
      <button class="abtn block" name="setup" value="1">Créer le mot de passe</button>
    </form>
  </div>
  <?php
  admin_footer();
  exit;
}

/* --- Mot de passe oublié : envoi d'un lien de réinitialisation par email --- */
if (isset($_GET['forgot']) && !is_logged_in()) {
  $sent = false;
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot'])) {
    $token = create_reset_token();
    if ($token !== '') { send_reset_email($token); }
    $sent = true; // message identique dans tous les cas (pas de fuite d'info)
  }
  admin_header('Mot de passe oublié');
  ?>
  <div class="acard alogin">
    <span class="amark big">ULMJC</span>
    <h1 class="atitle">Mot de passe oublié</h1>
    <?php if ($sent): ?>
      <p class="asub">Si un compte existe, un lien de réinitialisation vient d'être envoyé à l'adresse de l'association (<strong>ulmjc.gre@free.fr</strong>). Il est valable 1 heure. Pensez à vérifier les courriers indésirables.</p>
      <p style="margin-top:1.2rem;"><a class="abtn abtn-ghost block" href="index.php">Retour à la connexion</a></p>
    <?php else: ?>
      <p class="asub">Un lien de réinitialisation sera envoyé à l'adresse officielle de l'association. Cliquez pour l'envoyer.</p>
      <form method="post">
        <?= csrf_field() ?>
        <button class="abtn block" name="forgot" value="1">Envoyer le lien de réinitialisation</button>
      </form>
      <p style="margin-top:1rem;"><a class="alink" href="index.php">← Retour à la connexion</a></p>
    <?php endif; ?>
  </div>
  <?php
  admin_footer();
  exit;
}

/* --- Réinitialisation via le lien reçu par email --- */
if (isset($_GET['reset']) && !is_logged_in()) {
  $token = (string)$_GET['reset'];
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $token = (string)($_POST['token'] ?? '');
    $new = (string)($_POST['pass'] ?? '');
    $conf = (string)($_POST['confirm'] ?? '');
    if (!check_reset_token($token))    $error = 'Lien invalide ou expiré. Redemandez une réinitialisation.';
    elseif (mb_strlen($new) < 8)       $error = 'Choisissez un mot de passe d\'au moins 8 caractères.';
    elseif ($new !== $conf)            $error = 'La confirmation ne correspond pas.';
    elseif (consume_reset_and_set_pass($token, $new)) {
      $_SESSION['ulmjc_admin'] = true;
      session_regenerate_id(true);
      $_SESSION['ulmjc_admin'] = true;
      header('Location: index.php?ok=pass'); exit;
    } else {
      $error = 'Impossible d\'enregistrer le mot de passe (droits du dossier ?).';
    }
  }
  $valid = check_reset_token($token);
  admin_header('Réinitialisation');
  ?>
  <div class="acard alogin">
    <span class="amark big">ULMJC</span>
    <h1 class="atitle">Nouveau mot de passe</h1>
    <?php if (!$valid && !$error): ?>
      <p class="asub">Ce lien de réinitialisation est invalide ou a expiré (il n'est valable qu'une heure).</p>
      <p style="margin-top:1.2rem;"><a class="abtn abtn-ghost block" href="?forgot=1">Redemander un lien</a></p>
    <?php else: ?>
      <p class="asub">Choisissez votre nouveau mot de passe (au moins 8 caractères).</p>
      <?php if ($error): ?><p class="aerror"><?= e($error) ?></p><?php endif; ?>
      <form method="post">
        <input type="hidden" name="token" value="<?= e($token) ?>" />
        <label class="afield">Nouveau mot de passe
          <input type="password" name="pass" autofocus required minlength="8" />
        </label>
        <label class="afield">Confirmer le mot de passe
          <input type="password" name="confirm" required minlength="8" />
        </label>
        <button class="abtn block" name="reset" value="1">Enregistrer le nouveau mot de passe</button>
      </form>
    <?php endif; ?>
  </div>
  <?php
  admin_footer();
  exit;
}

/* --- Connexion --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
  if (check_admin_pass($_POST['pass'] ?? '')) {
    $_SESSION['ulmjc_admin'] = true;
    session_regenerate_id(true);
    $_SESSION['ulmjc_admin'] = true;
    header('Location: index.php'); exit;
  }
  $error = 'Mot de passe incorrect.';
}

if (!is_logged_in()) {
  admin_header('Connexion');
  ?>
  <div class="acard alogin">
    <span class="amark big">ULMJC</span>
    <h1 class="atitle">Espace de publication</h1>
    <p class="asub">Connectez-vous pour gérer les actualités.</p>
    <?php if ($error): ?><p class="aerror"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
      <label class="afield">Mot de passe
        <input type="password" name="pass" autofocus required />
      </label>
      <button class="abtn block" name="login" value="1">Se connecter</button>
    </form>
    <p style="margin-top:1rem;"><a class="alink" href="?forgot=1">Mot de passe oublié&nbsp;?</a></p>
  </div>
  <?php
  admin_footer();
  exit;
}

/* --- Tableau de bord : liste des actualités (hors corbeille) --- */
$actus = active_items('actus');
usort($actus, function ($x, $y) {
  return strcmp(($y['date'] ?? '') . ($y['created'] ?? ''), ($x['date'] ?? '') . ($x['created'] ?? ''));
});
$flash = isset($_GET['ok']) ? $_GET['ok'] : '';
$savedSlug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']) : '';

admin_header('Actualités');
?>
<?php if ($flash === 'saved'):
  $sv = $savedSlug ? find_actu($savedSlug) : null; ?>
  <div class="aflash">
    Actualité enregistrée.
    <?php if ($sv && !empty($sv['published'])): ?>
      <a class="aflash-link" href="../actu.php?slug=<?= e($sv['slug']) ?>" target="ulmjc_site">Voir l'actualité ↗</a>
    <?php elseif ($sv): ?>
      <span class="aflash-note">— brouillon, non visible sur le site</span>
    <?php endif; ?>
  </div>
<?php elseif ($flash === 'trashed'): ?><div class="aflash">Actualité déplacée vers la corbeille. <a class="aflash-link" href="corbeille.php">Voir la corbeille ↗</a></div>
<?php elseif ($flash === 'deleted'): ?><div class="aflash">Actualité supprimée.</div>
<?php elseif ($flash === 'pass'): ?><div class="aflash">Mot de passe mis à jour.</div><?php endif; ?>

<div class="ahead">
  <div>
    <h1 class="atitle">Actualités</h1>
    <p class="asub"><?= count($actus) ?> actualité<?= count($actus) > 1 ? 's' : '' ?></p>
  </div>
  <div class="ahead-actions">
    <a class="abtn" href="edit.php">+ Nouvelle actualité</a>
  </div>
</div>

<?php if (!$actus): ?>
  <div class="acard aempty">Aucune actualité pour le moment.<br/>Cliquez sur « Nouvelle actualité » pour publier la première.</div>
<?php else: ?>
  <div class="alist">
    <?php foreach ($actus as $a): ?>
      <div class="arow">
        <?php if (has_thumb($a)): ?>
          <span class="arow-cover" style="background-image:url('<?= e('../' . list_thumb($a)) ?>')"></span>
        <?php else: ?>
          <span class="arow-cover arow-cover--empty" aria-hidden="true">Sans photo</span>
        <?php endif; ?>
        <div class="arow-main">
          <div class="arow-title"><?= !empty($a['title']) ? e($a['title']) : '<span class="arow-untitled">' . e(display_title($a)) . '</span>' ?></div>
          <div class="arow-meta">
            <?= fr_date($a['date'] ?? '') ?>
            <?php if (empty($a['published'])): ?>
              <span class="abadge">Brouillon</span>
            <?php else: ?>
              <span class="abadge apub">En ligne</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="arow-actions">
          <a class="alink" href="edit.php?slug=<?= e($a['slug']) ?>">Modifier</a>
          <?php if (!empty($a['published'])): ?>
            <a class="alink" href="../actu.php?slug=<?= e($a['slug']) ?>" target="ulmjc_site">Voir</a>
          <?php endif; ?>
          <form method="post" action="delete.php" onsubmit="return confirm('Mettre cette actualité à la corbeille ?');">
            <?= csrf_field() ?>
            <input type="hidden" name="slug" value="<?= e($a['slug']) ?>" />
            <button class="alink adanger" name="del" value="1">Supprimer</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php admin_footer(); ?>
