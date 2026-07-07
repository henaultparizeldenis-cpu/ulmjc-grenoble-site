<?php
/* Changement de mot de passe. Basé sur mohamed-cms/site/admin/password.php.
   Durci : minimum 8 caractères, empreinte bcrypt (via set_admin_pass). */
require_once __DIR__ . '/auth.php';
require_login();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
  $cur = $_POST['current'] ?? '';
  $new = $_POST['new'] ?? '';
  $conf = $_POST['confirm'] ?? '';
  if (!check_admin_pass($cur))      $err = 'Mot de passe actuel incorrect.';
  elseif (mb_strlen($new) < 8)      $err = 'Le nouveau mot de passe est trop court (8 caractères minimum).';
  elseif ($new !== $conf)           $err = 'La confirmation ne correspond pas.';
  else { set_admin_pass($new); header('Location: index.php?ok=pass'); exit; }
}

admin_header('Mot de passe');
?>
<div class="ahead">
  <h1 class="atitle">Changer le mot de passe</h1>
  <a class="alink" href="index.php">← Retour</a>
</div>
<form class="acard aform" method="post" style="max-width:460px">
  <?= csrf_field() ?>
  <?php if ($err): ?><p class="aerror"><?= e($err) ?></p><?php endif; ?>
  <label class="afield">Mot de passe actuel
    <input type="password" name="current" required autofocus />
  </label>
  <label class="afield">Nouveau mot de passe <span class="ahint">(8 caractères minimum)</span>
    <input type="password" name="new" required minlength="8" />
  </label>
  <label class="afield">Confirmer le nouveau mot de passe
    <input type="password" name="confirm" required minlength="8" />
  </label>
  <div class="aactions">
    <button class="abtn" type="submit">Mettre à jour</button>
    <a class="alink" href="password.php">Annuler</a>
  </div>
</form>
<?php admin_footer(); ?>
