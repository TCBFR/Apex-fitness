<?php
$activites = $activites ?? [];
$pageActive = 'accueil';
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
  <link rel="stylesheet" href="<?= e(url('assets/css/site_nav.css?v=4')) ?>">
</head>
<body>
<?php require_once __DIR__ . '/../../layout/site_nav.php'; ?>

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
    <article class="feature" id="equipe" data-reveal>
      <span class="f-icon" aria-hidden="true">◎</span>
      <div><h3>Coachs à l’écoute</h3><p>Des conseils adaptés à tes objectifs.</p></div>
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
          $typeActivite = (string) ($activite['type_activite'] ?? 'cross');
          $imagesActivites = [
              'cross' => 'cross.png', 'pilates' => 'pilate.png', 'abdos' => 'abdos.png',
              'boxe' => 'boxe.png', 'danse' => 'danse.png', 'yoga' => 'yogas.png',
              'cardio' => 'cross.png', 'musculation' => 'cross.png',
          ];
          $imageActivite = $activite['image_path'] ?: 'assets/img/' . ($imagesActivites[$typeActivite] ?? 'cross.png');
          $descriptionImage = 'Séance de ' . (ActiviteCatalogue::TYPES[$typeActivite] ?? 'sport');
          $descriptionActivite = (string) ($activite['description'] ?? '');
          ?>
          <article class="card-h" data-reveal>
            <div class="card-media">
              <img src="<?= e(url($imageActivite)) ?>" alt="<?= e($descriptionImage) ?>" loading="lazy">
            </div>
            <div class="card-body">
              <h3><?= e($activite['titre'] ?? 'Activité') ?></h3>
              <p class="activity-description"><?= e($descriptionActivite) ?></p>
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

  <section class="testimonials section" id="temoignages" aria-labelledby="testimonials-title">
    <div class="section-head" data-reveal>
      <p class="eyebrow">Témoignages membres</p>
      <h2 class="section-title" id="testimonials-title">Ils nous font <em>confiance</em></h2>
      <p class="section-sub">Leurs objectifs, leurs progrès et leur expérience chez ATHLETIX.</p>
    </div>
    <div class="testimonial-carousel" data-testimonial-carousel aria-roledescription="carrousel" aria-label="Témoignages des membres">
      <?php
      $temoignages = [
          ['Lara Smirnova', '4 ans', 'Le sport, c’est bien plus qu’un entraînement. C’est une façon de se sentir plus forte, de prendre sa place et de repousser ses limites. À chaque entraînement, je construis une base solide pour ma santé et ma confiance.', 'img2.png'],
          ['Yanis Moreau', '3 ans', 'J’ai retrouvé le plaisir de bouger. Les séances me donnent l’énergie nécessaire pour me dépasser à mon rythme, et chaque progrès me motive à continuer.', 'img1.png'],
          ['Inès Bernard', '1 an', 'L’équipe m’a aidée à trouver une pratique qui me ressemble. Je me sens plus sereine, plus souple et plus forte à chaque séance.', 'img4.png'],
          ['Thomas Leroy', '2 ans', 'Une super ambiance et des coachs toujours à l’écoute. Je progresse tout en prenant vraiment plaisir à venir, semaine après semaine.', 'img3.png'],
      ];
      foreach ($temoignages as $index => [$nom, $activite, $citation, $imageTemoignage]):
      ?>
        <article class="testimonial-slide<?= $index === 0 ? ' is-active' : '' ?>" data-testimonial-slide aria-roledescription="diapositive" aria-label="<?= $index + 1 ?> sur <?= count($temoignages) ?>"<?= $index === 0 ? '' : ' hidden' ?>>
          <div class="testimonial-copy">
            <span class="testimonial-mark" aria-hidden="true">“</span>
            <blockquote><?= e($citation) ?></blockquote>
            <div class="testimonial-member">
              <div><h3><?= e($nom) ?></h3><p>Membre ATHLETIX</p></div>
              <span class="member-activity"><?= e($activite) ?> chez ATHLETIX</span>
            </div>
          </div>
          <div class="testimonial-photo"><img src="<?= e(url('assets/img/' . $imageTemoignage)) ?>" alt="Portrait d’un membre ATHLETIX à l’entraînement" loading="lazy"></div>
        </article>
      <?php endforeach; ?>
      <div class="testimonial-controls" aria-label="Commandes du carrousel">
        <button class="testimonial-arrow" type="button" data-testimonial-prev aria-label="Témoignage précédent">←</button>
        <div class="testimonial-dots" aria-label="Choisir un témoignage">
          <?php foreach ($temoignages as $index => $temoignage): ?>
            <button type="button" data-testimonial-dot="<?= $index ?>" aria-label="Afficher le témoignage <?= $index + 1 ?>"<?= $index === 0 ? ' aria-current="true"' : '' ?>></button>
          <?php endforeach; ?>
        </div>
        <button class="testimonial-arrow" type="button" data-testimonial-next aria-label="Témoignage suivant">→</button>
      </div>
    </div>
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
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
<script src="<?= e(url('assets/js/pages/home.js')) ?>" defer></script>
</body>
</html>
