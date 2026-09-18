<?php $pageActive = $pageActive ?? ''; $pagesJs = $pagesJs ?? []; ?>
</main>

<footer class="site-footer">
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
        <a href="<?= url('abonnements.php') ?>">Abonnements</a><br>
        <a href="<?= url('faq.php') ?>">Questions fréquentes</a><br>
        <a href="<?= url('contact.php') ?>">Contact</a><br>
        <a href="<?= url('login.php') ?>">Espace membre</a>
      </p>
    </div>
  </div>
  <div class="wrap footer-bas">
    <p>Projet BTS SIO SLAM &ndash; application de gestion de salle de sport.</p>
  </div>
</footer>

<script src="<?= url('assets/js/app.js') ?>"></script>
<?php if (isset($pagesJs[$pageActive])): ?>
<script src="<?= url('assets/js/pages/' . $pagesJs[$pageActive]) ?>"></script>
<?php endif; ?>
</body>
</html>
