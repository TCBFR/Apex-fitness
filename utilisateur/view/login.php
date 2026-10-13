<?php
$erreur = $erreur ?? null;
$mail = $mail ?? '';
$comptesDemo = $comptesDemo ?? [];
$messagesFlash = flashs();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion — ATHLETIX</title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'><path d='M24 3 44 44H34L24 22 14 44H4Z' fill='%23c8f04b'/></svg>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/pages/login.css')) ?>">
</head>
<body>
<main class="stage" id="stage">
  <aside class="visual" aria-hidden="true">
    <div class="pan"><img src="<?= e(url('assets/img/login.png')) ?>" alt=""></div>
    <div class="visual-fade"></div>
  </aside>

  <section class="side">
    <div class="stripes" aria-hidden="true"></div>
    <div class="auth" id="card" data-testid="login-card">
      <a class="brandline brand-home" href="<?= e(url('accueil/index.php')) ?>" aria-label="Retour à l’accueil">
        <svg class="logo" viewBox="0 0 64 64" aria-hidden="true"><path d="M32 5 59 59H45.5L32 32.5 18.5 59H5Z" fill="currentColor"/></svg>
        <div>
          <p class="brandname" data-testid="brand-wordmark">ATHLETI<span>X</span></p>
          <p class="member">ESPACE MEMBRE</p>
        </div>
      </a>

      <header class="panel-head">
        <h1 class="panel-title" data-testid="login-title"><span class="line"><span data-reveal style="--d:.2s">Bon retour,</span></span></h1>
        <p class="panel-sub" data-reveal style="--d:.34s">Connectez-vous à votre espace personnel.</p>
      </header>

      <?php foreach ($messagesFlash as $message): ?>
        <p class="login-message" role="status"><?= e($message['message']) ?></p>
      <?php endforeach; ?>
      <?php if ($erreur): ?>
        <p class="login-message login-error" role="alert" data-testid="login-error"><?= e($erreur) ?></p>
      <?php endif; ?>

      <form class="form" id="login-form" data-testid="login-form" method="post" novalidate>
        <?= champ_csrf() ?>
        <div class="field" data-reveal style="--d:.46s">
          <div class="input-wrap">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3.5 7 8.5 6 8.5-6"/></svg>
            <input type="email" id="email" name="mail" value="<?= e($mail) ?>" data-testid="email-input" placeholder="Adresse e-mail" autocomplete="username" required>
          </div>
          <p class="field-msg" data-testid="email-error"></p>
        </div>

        <div class="field" data-reveal style="--d:.56s">
          <div class="input-wrap">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/></svg>
            <input type="password" id="password" name="motdepasse" data-testid="password-input" placeholder="Mot de passe" autocomplete="current-password" required>
            <button type="button" class="eye" id="toggle-password" data-testid="toggle-password-button" aria-label="Afficher le mot de passe">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/><path d="m4 4 16 16"/></svg>
            </button>
          </div>
          <p class="field-msg" data-testid="password-error"></p>
        </div>

        <div class="row" data-reveal style="--d:.66s">
          <label class="remember">
            <input type="checkbox" name="se_souvenir" value="1">
            <span class="box" aria-hidden="true"></span>
            <span class="check-label">Se souvenir de moi</span>
          </label>
          <a href="<?= e(url('message_contact/index.php')) ?>" class="link">Mot de passe oublié ?</a>
        </div>

        <button type="submit" class="btn" id="signin-btn" data-testid="sign-in-button" data-reveal style="--d:.78s">
          <span class="btn-label">Se connecter
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg>
          </span>
          <span class="btn-spinner" aria-hidden="true"></span>
        </button>
      </form>

      <div class="divider" data-reveal style="--d:.88s"><span>OU</span></div>
      <a href="<?= e(url('utilisateur/inscription.php')) ?>" class="btn-ghost" data-testid="login-signup-link" data-reveal style="--d:.96s">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3.6"/><path d="M5 20c1.2-3.4 3.8-5 7-5s5.8 1.6 7 5"/></svg>
        Inscription
      </a>

      <p class="secure" data-reveal style="--d:1.04s">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/></svg>
        Connexion sécurisée · Vos données sont protégées
      </p>

      <?php if ($comptesDemo): ?>
        <div class="demo-list" data-reveal style="--d:1.12s">
          <p>Comptes de démonstration</p>
          <?php foreach ($comptesDemo as [$emailDemo, $roleDemo, $passwordDemo]): ?>
            <button type="button" class="demo demo-account" data-testid="demo-fill-button"
                    data-email="<?= e($emailDemo) ?>" data-password="<?= e($passwordDemo) ?>">
              <?= e($emailDemo) ?> <span>— <?= e(ucfirst($roleDemo)) ?></span>
            </button>
          <?php endforeach; ?>
          <small>Mot de passe des comptes démo initialisés : <code>apex2026</code></small>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
<script src="<?= e(url('assets/js/pages/login.js')) ?>" defer></script>
</body>
</html>
