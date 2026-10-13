<?php
$activites = $activites ?? [];
$activeNav = $activeNav ?? 'accueil';
$navLinks = [
    'accueil' => ['Accueil', url('accueil/index.php')],
    'activites' => ['Activités', '#activites'],
    'abonnements' => ['Abonnements', url('abonnement/catalogue.php')],
    'avis' => ['Avis', url('avis/public.php')],
    'faq' => ['FAQ', url('faq/public.php')],
    'contact' => ['Contact', url('message_contact/index.php')],
];
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="ATHLETIX : salle de sport, cours collectifs et accompagnement sportif.">
  <title>ATHLETIX — Salle de sport et cours collectifs</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/pages/home.css')) ?>">
</head>
<body>
<header class="nav" id="nav" data-testid="main-nav">
  <a class="nav-brand" href="<?= e(url('accueil/index.php')) ?>" data-testid="nav-brand" aria-label="ATHLETIX, accueil">
    <svg viewBox="0 0 64 64" aria-hidden="true"><path d="M32 5 59 59H45.5L32 32.5 18.5 59H5Z" fill="currentColor"/></svg>
    <span class="nav-brand-text">
      <span class="nav-name">ATHLETI<span>X</span></span>
      <span class="nav-tag">PLUS FORT CHAQUE JOUR</span>
    </span>
  </a>

  <nav class="nav-links" id="nav-links" aria-label="Navigation principale">
    <?php foreach ($navLinks as $key => [$label, $href]): ?>
      <a href="<?= e($href) ?>" class="<?= $key === $activeNav ? 'active' : '' ?>" <?= $key === $activeNav ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
    <a class="nav-login mobile-only" href="<?= e(url('utilisateur/connexion.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.6"/><path d="M5 20c1.2-3.4 3.8-5 7-5s5.8 1.6 7 5"/></svg>
      Connexion adhérent
    </a>
  </nav>

  <div class="nav-actions">
    <a class="nav-login desktop-login" href="<?= e(url('utilisateur/connexion.php')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.6"/><path d="M5 20c1.2-3.4 3.8-5 7-5s5.8 1.6 7 5"/></svg>
      Connexion adhérent
    </a>
    <button class="nav-toggle" id="nav-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="nav-links">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<main id="main">
  <section class="hero" data-testid="hero-section">
    <div class="hero-media" aria-hidden="true"><img src="<?= e(url('assets/img/home-hero.jpg')) ?>" alt=""></div>
    <div class="hero-inner">
      <p class="eyebrow" data-reveal>Salle de sport <i>|</i> Cours collectifs <i>|</i> Bien-être</p>
      <h1 class="hero-title" data-testid="hero-title">
        <span class="line"><span data-reveal>Dépasse tes limites.</span></span>
        <span class="line lime"><span data-reveal>À ton rythme.</span></span>
      </h1>
      <p class="hero-sub" data-reveal>Musculation, cardio et cours collectifs : trouve l’activité qui te motive et progresse avec une équipe à tes côtés.</p>
      <div class="hero-ctas" data-reveal>
        <a class="btn btn-lime" href="<?= e(url('utilisateur/inscription.php')) ?>">Commencer maintenant <span aria-hidden="true">→</span></a>
        <a class="btn btn-ghost-h" href="#activites">Découvrir les activités</a>
      </div>
      <p class="hero-note" data-reveal>Un espace conçu pour avancer, séance après séance.</p>
    </div>
  </section>

  <section class="features" aria-label="Les avantages ATHLETIX">
    <article class="feature" data-reveal>
      <span class="f-icon" aria-hidden="true">↗</span>
      <div><h3>Équipements modernes</h3><p>Tout le nécessaire pour t’entraîner efficacement.</p></div>
    </article>
    <article class="feature" data-reveal>
      <span class="f-icon" aria-hidden="true">◎</span>
      <div><h3>Coaches à l’écoute</h3><p>Des conseils adaptés à tes objectifs.</p></div>
    </article>
    <article class="feature" data-reveal>
      <span class="f-icon" aria-hidden="true">◇</span>
      <div><h3>Des cours pour tous</h3><p>Choisis ton rythme et trouve ta discipline.</p></div>
    </article>
  </section>

  <section class="activities section" id="activites" data-testid="activities-section">
    <div class="section-head" data-reveal>
      <p class="eyebrow">Bouge comme tu veux</p>
      <h2 class="section-title">Trouve ton <em>entraînement</em></h2>
      <p class="section-sub">Des séances variées pour te dépasser, te défouler ou simplement prendre soin de toi.</p>
    </div>
    <?php if ($activites): ?>
      <div class="cards">
        <?php foreach ($activites as $activite): ?>
          <?php
          $titreActivite = mb_strtolower((string) ($activite['titre'] ?? ''));
          $imageActivite = 'home-musculation.jpg';
          $descriptionImage = 'Haltères dans une salle de sport';
          if (str_contains($titreActivite, 'yoga')) {
              $imageActivite = 'home-yoga.jpg';
              $descriptionImage = 'Séance de yoga en studio';
          } elseif (str_contains($titreActivite, 'cardio') || str_contains($titreActivite, 'hiit') || str_contains($titreActivite, 'cycling')) {
              $imageActivite = 'home-cardio.jpg';
              $descriptionImage = 'Séance de cardio en salle';
          }
          ?>
          <article class="card-h" data-reveal>
            <div class="card-media">
              <img src="<?= e(url('assets/img/' . $imageActivite)) ?>" alt="<?= e($descriptionImage) ?>" loading="lazy">
            </div>
            <div class="card-body">
            <p class="activity-label">
              <?= e((new DateTimeImmutable((string) $activite['date_heure_debut']))->format('d/m à H\hi')) ?>
            </p>
            <h3><?= e($activite['titre'] ?? 'Activité') ?></h3>
            <p class="activity-description"><?= e($activite['description'] ?? 'Séance encadrée dans notre salle.') ?></p>
            <div class="activity-meta">
              <span><?= (int) ($activite['duree_minutes'] ?? 0) ?> min</span>
              <span><?= (int) ($activite['capacite_max'] ?? 0) ?> places</span>
            </div>
            <a href="<?= e(url('activite/index.php')) ?>" class="text-link">Voir les activités <span aria-hidden="true">→</span></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state" data-reveal>
        <h3>Les prochaines séances arrivent bientôt.</h3>
        <p>Découvre les activités proposées par ATHLETIX en attendant.</p>
        <a class="text-link" href="<?= e(url('activite/index.php')) ?>">Voir les activités <span aria-hidden="true">→</span></a>
      </div>
    <?php endif; ?>
  </section>

  <section class="cta" id="cta">
    <div class="cta-media" aria-hidden="true"><img src="<?= e(url('assets/img/home-musculation.jpg')) ?>" alt=""></div>
    <div class="cta-inner" data-reveal>
      <p class="eyebrow">Le prochain pas est le tien</p>
      <h2 class="cta-title">Prêt à passer au <em>niveau supérieur&nbsp;?</em></h2>
      <p class="cta-sub">Crée ton espace membre et découvre les offres qui correspondent à tes objectifs.</p>
      <div class="hero-ctas">
        <a class="btn btn-lime" href="<?= e(url('utilisateur/inscription.php')) ?>">Créer mon compte <span aria-hidden="true">→</span></a>
        <a class="btn btn-ghost-h" href="<?= e(url('abonnement/catalogue.php')) ?>">Voir les abonnements</a>
      </div>
    </div>
  </section>
</main>

<footer class="footer">
  <svg viewBox="0 0 64 64" aria-hidden="true"><path d="M32 5 59 59H45.5L32 32.5 18.5 59H5Z" fill="currentColor"/></svg>
  <p>ATHLETIΞ — Plus fort chaque jour · <a href="<?= e(url('utilisateur/connexion.php')) ?>">Espace membre</a></p>
</footer>
<script src="<?= e(url('assets/js/pages/home.js')) ?>" defer></script>
</body>
</html>
