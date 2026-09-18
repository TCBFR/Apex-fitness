<?php $activites = $activites ?? []; require __DIR__ . '/../includes/header.php'; ?>
<section class="hero">
  <div class="hero-inner">
    <div class="hero-photo"><img src="<?= url('assets/img/section1.png') ?>" alt="" class="hero-photo-img"></div>
    <div class="hero-texte-wrap"><div class="hero-texte">
      <h1>Votre créneau vous attend.</h1>
      <p>Cross-training, yoga, cycling ou musculation en accès libre : consultez le planning, réservez votre place en deux clics et venez vous entraîner.</p>
      <div class="hero-actions"><a class="btn" href="#planning">Voir le planning</a><a class="btn btn-ghost" href="<?= url('abonnements.php') ?>">Comparer les abonnements</a></div>
    </div></div>
  </div>
</section>
<section class="section-objectifs"><img src="<?= url('assets/img/objectifs.jpg') ?>" alt="Des objectifs réels, des résultats durables." class="objectifs-img"></section>
<section id="planning" class="section section-alt"><div class="wrap"><div class="section-head"><h2>Ce que vous pouvez pratiquer</h2><p class="muted">Six activités encadrées ou en accès libre, réparties sur trois salles.</p></div>
  <div class="grille-3">
    <?php foreach ($activites as $activite): ?>
      <article class="carte"><h3><?= e($activite['libelleact']) ?></h3><p class="mono"><?= $activite['duree_minutes'] ? (int) $activite['duree_minutes'] . ' min' : 'Accès libre' ?> &middot; <?= (int) $activite['capacite_defaut'] ?> places</p><p class="muted"><?= e($activite['descriptionact']) ?></p></article>
    <?php endforeach; ?>
  </div>
</div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
