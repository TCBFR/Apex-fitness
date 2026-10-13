<?php $activites = $activites ?? []; require __DIR__ . '/../../layout/header.php'; ?>
<section class="hero">
  <div class="hero-content">
    <div class="hero-text">
      <h1>Votre créneau vous attend.</h1>
      <p>Cross-training, yoga, cycling ou musculation en accès libre : consultez le planning, réservez votre place en deux clics et venez vous entraîner.</p>
      <div class="hero-buttons">
        <a class="btn btn-primary" href="#planning">Voir le planning</a>
        <a class="btn btn-secondary" href="<?= url('abonnement/catalogue.php') ?>">Comparer les abonnements</a>
      </div>
    </div>
    <div class="hero-image">
      <img src="<?= url('assets/img/section1.png') ?>" alt="Entraînement en salle" class="hero-img">
    </div>
  </div>
</section>

<section class="section section-objectifs">
  <div class="objectifs-content">
    <img src="<?= url('assets/img/accueil.png') ?>" alt="Des objectifs réels, des résultats durables." class="objectifs-img">
    <div class="objectifs-text">
      <h2>Des objectifs réels, des résultats durables.</h2>
      <p>Notre approche personnalisée vous permet d'atteindre vos ambitions sportives avec des résultats concrets et durables.</p>
    </div>
  </div>
</section>

<section id="planning" class="section section-planning">
  <div class="container">
    <div class="section-header">
      <h2>Prochaines séances</h2>
      <p class="section-subtitle">Choisissez une séance et consultez les disponibilités dans le planning.</p>
    </div>
    
    <?php if (!$activites): ?>
      <p class="section-subtitle">Aucune séance n’est programmée pour le moment.</p>
    <?php else: ?>
    <div class="activities-grid">
      <?php foreach ($activites as $activite): ?>
        <div class="activity-card">
          <div class="activity-header">
            <h3><?= e($activite['titre']) ?></h3>
          </div>
          <div class="activity-body">
            <div class="activity-details">
              <span class="duration"><?= (int) $activite['duree_minutes'] ?> min</span>
              <span class="capacity"><?= (int) $activite['capacite_max'] ?> places</span>
            </div>
            <p class="activity-description"><?= e($activite['description'] ?? '') ?></p>
          </div>
          <div class="activity-footer">
            <a href="<?= e(url(a_role('ADHERENT') ? 'adherent/view/planning.php' : 'utilisateur/connexion.php')) ?>" class="btn btn-small">Réserver</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section-features">
  <div class="container">
    <div class="features-grid">
      <div class="feature-card">
        <img src="<?= url('assets/img/header.png') ?>" alt="Équipements modernes" class="feature-img">
        <h3>Équipements modernes</h3>
        <p>Notre salle est équipée de machines de pointe pour un entraînement optimal.</p>
      </div>
      <div class="feature-card">
        <img src="<?= url('assets/img/section1.png') ?>" alt="Entraîneurs certifiés" class="feature-img">
        <h3>Entraîneurs certifiés</h3>
        <p>Nos coachs sont formés aux dernières techniques de fitness et nutrition.</p>
      </div>
    </div>
  </div>
</section>

<style>
  /* Styles pour l'interface professionnelle et fitness */
/* Styles pour l'interface professionnelle et fitness */
.hero {
  background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
  color: white;
  padding: 4rem 0;
  margin-bottom: 3rem;
}

.hero-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  gap: 2rem;
}

.hero-text h1 {
  font-size: 2.5rem;
  margin-bottom: 1rem;
  line-height: 1.2;
}

.hero-text p {
  font-size: 1.1rem;
  margin-bottom: 2rem;
  opacity: 0.9;
}

.hero-buttons {
  display: flex;
  gap: 1rem;
}

.btn-primary {
  background: #ff6b6b;
  color: white;
  border: none;
  padding: 0.8rem 1.5rem;
  border-radius: 30px;
  text-decoration: none;
  font-weight: bold;
  transition: all 0.3s ease;
}

.btn-primary:hover {
  background: #ff5252;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
}

.btn-secondary {
  background: transparent;
  color: white;
  border: 2px solid white;
  padding: 0.8rem 1.5rem;
  border-radius: 30px;
  text-decoration: none;
  font-weight: bold;
  transition: all 0.3s ease;
}

.btn-secondary:hover {
  background: white;
  color: #2c5364;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
}

.hero-image {
  flex: 1;
  text-align: center;
}

.hero-img {
  max-width: 100%;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.section-objectifs {
  background: #f8f9fa;
  padding: 4rem 0;
  margin-bottom: 3rem;
}

.objectifs-content {
  display: flex;
  align-items: center;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  gap: 3rem;
}

.objectifs-img {
  flex: 1;
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.objectifs-text {
  flex: 1;
}

.objectifs-text h2 {
  font-size: 2rem;
  margin-bottom: 1rem;
  color: #2c5364;
}

.section-planning {
  padding: 4rem 0;
  background: white;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;
}

.section-header h2 {
  font-size: 2rem;
  color: #2c5364;
  margin-bottom: 1rem;
}

.section-subtitle {
  color: #666;
  font-size: 1.1rem;
}

.activities-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.activity-card {
  background: white;
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: all 0.3s ease;
  border: 1px solid #eee;
}

.activity-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.activity-header {
  background: #2c5364;
  color: white;
  padding: 1.5rem;
}

.activity-header h3 {
  margin: 0;
  font-size: 1.4rem;
}

.activity-body {
  padding: 1.5rem;
}

.activity-details {
  display: flex;
  justify-content: space-between;
  margin-bottom: 1rem;
  color: #666;
  font-size: 0.9rem;
}

.duration, .capacity {
  background: #f0f8ff;
  padding: 0.3rem 0.8rem;
  border-radius: 20px;
  font-weight: bold;
}

.activity-description {
  color: #555;
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.activity-footer {
  padding: 0 1.5rem 1.5rem;
}

.btn-small {
  background: #ff6b6b;
  color: white;
  border: none;
  padding: 0.6rem 1.2rem;
  border-radius: 20px;
  text-decoration: none;
  font-size: 0.9rem;
  transition: all 0.3s ease;
}

.btn-small:hover {
  background: #ff5252;
  transform: scale(1.05);
}

.section-features {
  padding: 4rem 0;
  background: #f8f9fa;
  margin-top: 3rem;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.feature-card {
  background: white;
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  padding: 2rem;
  text-align: center;
  transition: all 0.3s ease;
}

.feature-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.feature-img {
  width: 100%;
  height: 200px;
  object-fit: cover;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.feature-card h3 {
  color: #2c5364;
  margin-bottom: 1rem;
}

@media (max-width: 768px) {
  .hero-content {
    flex-direction: column;
    text-align: center;
  }
  
  .objectifs-content {
    flex-direction: column;
  }
  
  .hero-buttons {
    justify-content: center;
  }
}
</style>
<?php require __DIR__ . '/../../layout/footer.php'; ?>
