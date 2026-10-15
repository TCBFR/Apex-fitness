<?php
$donnees = $donnees ?? [
    'nom' => '', 'prenom' => '', 'date_naissance' => '',
    'mail' => '', 'adresse_postale' => '',
];
$commune = $commune ?? ['id_commune' => '', 'nom_commune' => '', 'code_postal' => ''];
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
  <link rel="stylesheet" href="<?= e(url('assets/css/commune/commune.css')) ?>">
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
        <div class="field commune-recherche">
          <div class="input-wrap"><input type="text" id="commune" value="<?= e((string) $commune['nom_commune']) ?>" data-url="<?= e(url('commune/recherche.php')) ?>" placeholder="Commune (saisir au moins 2 caractères)" aria-label="Commune" autocomplete="off" required></div>
          <div class="commune-resultats" id="commune-resultats" hidden></div>
          <input type="hidden" name="id_commune" id="id_commune" value="<?= e((string) $commune['id_commune']) ?>">
          <p id="commune-message" aria-live="polite"></p>
          <p class="field-msg"></p>
        </div>
        <div class="field">
          <div class="input-wrap"><input type="text" id="code_postal" value="<?= e((string) $commune['code_postal']) ?>" placeholder="Code postal" aria-label="Code postal" readonly tabindex="-1"></div>
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
<script src="<?= e(url('assets/js/commune/commune.js')) ?>" defer></script>
</body>
</html>