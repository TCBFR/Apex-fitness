<?php
$donnees = $donnees ?? [
    'nom' => '', 'prenom' => '', 'date_naissance' => '', 'ville' => '',
    'code_postal' => '', 'mail' => '', 'adresse_postale' => '',
];
$erreur = $erreur ?? null;
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inscription — ATHLETIX</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/pages/login.css')) ?>">
</head>
<body class="signup-page">
<main class="stage" id="stage">
  <aside class="visual" aria-hidden="true">
    <div class="pan"><img src="<?= e(url('assets/img/login.png')) ?>" alt=""></div>
    <div class="visual-fade"></div>
  </aside>
  <section class="side">
    <div class="auth" data-testid="signup-card">
      <a class="brandline brand-home" href="<?= e(url('accueil/index.php')) ?>" aria-label="Retour à l’accueil">
        <svg class="logo" viewBox="0 0 64 64" aria-hidden="true"><path d="M32 5 59 59H45.5L32 32.5 18.5 59H5Z" fill="currentColor"/></svg>
        <div><p class="brandname">ATHLETI<span>X</span></p><p class="member">ESPACE MEMBRE</p></div>
      </a>
      <header class="panel-head">
        <h1 class="panel-title" data-testid="signup-title">Inscription</h1>
        <p class="panel-sub">Créez votre espace adhérent.</p>
      </header>

      <?php if ($erreur): ?>
        <p class="login-message login-error" role="alert" data-testid="signup-error"><?= e($erreur) ?></p>
      <?php endif; ?>

      <form class="form" id="signup-form" method="post" novalidate>
        <?= champ_csrf() ?>
        <div class="field">
          <div class="input-wrap"><input type="text" name="nom" id="nom" value="<?= e($donnees['nom']) ?>" placeholder="Nom" autocomplete="family-name" maxlength="60" required></div>
          <p class="field-msg"></p>
        </div>
        <div class="field">
          <div class="input-wrap"><input type="text" name="prenom" id="prenom" value="<?= e($donnees['prenom']) ?>" placeholder="Prénom" autocomplete="given-name" maxlength="60" required></div>
          <p class="field-msg"></p>
        </div>
        <div class="field">
          <div class="input-wrap"><input type="text" name="date_naissance" id="date_naissance" value="<?= e($donnees['date_naissance']) ?>" placeholder="JJ-MM-AAAA" aria-label="Date de naissance (jour-mois-année)" autocomplete="bday" inputmode="numeric" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" maxlength="10" required></div>
          <p class="field-msg"></p>
        </div>
        <div class="field">
          <div class="input-wrap"><input type="text" name="ville" id="ville" value="<?= e($donnees['ville']) ?>" placeholder="Ville" autocomplete="address-level2" maxlength="100" required></div>
          <p class="field-msg"></p>
        </div>
        <div class="field">
          <div class="input-wrap"><input type="text" name="code_postal" id="code_postal" value="<?= e($donnees['code_postal']) ?>" placeholder="Code postal" autocomplete="postal-code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" required></div>
          <p class="field-msg"></p>
        </div>
        <div class="field">
          <div class="input-wrap"><input type="email" name="mail" id="email" value="<?= e($donnees['mail']) ?>" placeholder="Adresse e-mail" autocomplete="email" maxlength="150" required></div>
          <p class="field-msg"></p>
        </div>
        <div class="field">
          <div class="input-wrap"><input type="text" name="adresse_postale" id="adresse_postale" value="<?= e($donnees['adresse_postale']) ?>" placeholder="Adresse postale" autocomplete="street-address" maxlength="255" required></div>
          <p class="field-msg"></p>
        </div>
        <div class="field">
          <div class="input-wrap"><input type="password" name="motdepasse" id="password" placeholder="Mot de passe (8 caractères minimum)" autocomplete="new-password" minlength="8" required></div>
          <p class="field-msg"></p>
        </div>
        <div class="field">
          <div class="input-wrap"><input type="password" name="confirmation" id="confirmation" placeholder="Confirmer le mot de passe" autocomplete="new-password" required></div>
          <p class="field-msg"></p>
        </div>
        <button type="submit" class="btn" id="signup-button" data-testid="sign-up-button">
          <span class="btn-label">Créer mon compte</span><span class="btn-spinner" aria-hidden="true"></span>
        </button>
      </form>

      <div class="divider"><span>OU</span></div>
      <a href="<?= e(url('utilisateur/connexion.php')) ?>" class="btn-ghost">Déjà inscrit ? Se connecter</a>
      <p class="secure">Votre compte adhérent sera créé après validation.</p>
    </div>
  </section>
</main>
<script src="<?= e(url('assets/js/pages/signup.js')) ?>" defer></script>
</body>
</html>
