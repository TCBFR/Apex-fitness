<div class="pe<?= !empty($accueil) ? ' pe-reception' : '' ?>" id="pe-root" data-testid="planning-employe">
  <?php if (!empty($accueil)): ?>
    <header class="pe-reception-heading">
      <span class="pe-reception-heading-icon" aria-hidden="true"><i class="fa-regular fa-calendar"></i></span>
      <div><h2>Planning des cours</h2><p>Consultez et gérez le planning des activités du centre sportif.</p></div>
    </header>
  <?php endif; ?>
  

  <?php if (empty($accueil)): ?>
  <section class="pe-stats" data-testid="planning-stats">
    <div class="pe-stat"><b id="pe-stat-count" data-testid="stat-sessions">0</b><span>séances sur la période</span></div>
    <div class="pe-stat"><b id="pe-stat-fill" data-testid="stat-fill">—</b><span>taux de remplissage moyen</span></div>
    <div class="pe-stat is-warn"><b id="pe-stat-full" data-testid="stat-full">0</b><span>séances complètes</span></div>
    <div class="pe-stat is-bad"><b id="pe-stat-low" data-testid="stat-low">0</b><span>séances peu remplies (&lt; 30&nbsp;%)</span></div>
  </section>
  <?php endif; ?>

