<?php $pageActive = $pageActive ?? ''; $pagesJs = $pagesJs ?? []; ?>
</main>

<footer class="site-footer">
<?php if (in_array($pageActive, ['abonnements', 'avis', 'faq', 'contact'], true)): ?>
  <div class="container footer-inner">
    <a href="<?= e(url('accueil/index.php')) ?>" class="logo logo-sm" aria-label="ATHLETIX"><svg class="logo-mark" viewBox="0 0 40 36" aria-hidden="true"><path d="M20 0 40 36H29.5L20 18.5 10.5 36H0Z" fill="currentColor"/></svg><span class="logo-text">ATHLETIX</span></a>
    <p class="footer-copy">&copy; <?= date('Y') ?> ATHLETIX &middot; 12 Rue des Sports, 75000 Paris &middot; +33 1 23 45 67 89</p>
    <nav class="footer-links" aria-label="Liens de pied de page"><a href="<?= e(url('accueil/index.php') . '#activites') ?>">Activités</a><a href="<?= e(url('abonnement/catalogue.php')) ?>">Abonnements</a><a href="<?= e(url('avis/public.php')) ?>">Avis</a><a href="<?= e(url('faq/public.php')) ?>">FAQ</a><a href="<?= e(url('message_contact/index.php')) ?>">Contact</a></nav>
  </div>
<?php else: ?>
  <div class="wrap footer-grid">
    <div>
      <p class="footer-titre">Apex Fitness</p>
      <p>12 avenue des Sports<br>19100 Brive-la-Gaillarde</p>
      <p>05 55 10 20 30</p>
    </div>
    <div>
      <p class="footer-titre">Horaires</p>
      <p>Lundi au vendredi : 6h30 &ndash; 22h30<br>
         Samedi : 8h &ndash; 20h<br>
         Dimanche : 9h &ndash; 13h</p>
    </div>
    <div>
      <p class="footer-titre">Le site</p>
      <p>
        <a href="<?= url('abonnement/catalogue.php') ?>">Abonnements</a><br>
        <a href="<?= url('faq/public.php') ?>">Questions fréquentes</a><br>
        <a href="<?= url('message_contact/index.php') ?>">Contact</a><br>
        <a href="<?= url('utilisateur/connexion.php') ?>">Espace membre</a>
      </p>
    </div>
  </div>
  <div class="wrap footer-bas">
    <p>Projet BTS SIO SLAM &ndash; application de gestion de salle de sport.</p>
  </div>
<?php endif; ?>
</footer>

<script src="<?= url('assets/js/app.js') ?>"></script>
<?php if (in_array($pageActive, ['abonnements', 'avis', 'faq', 'contact'], true)): ?>
<script src="<?= url('assets/js/pages/athletix.js') ?>"></script>
<?php endif; ?>
<?php if (isset($pagesJs[$pageActive])): ?>
<script src="<?= url('assets/js/pages/' . $pagesJs[$pageActive]) ?>"></script>
<?php endif; ?>
</body>
</html>
