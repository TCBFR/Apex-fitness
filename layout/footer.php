<?php $pageActive = $pageActive ?? ''; $pagesJs = $pagesJs ?? []; ?>
</main>

<footer class="footer">
  <svg viewBox="0 0 64 64" aria-hidden="true"><path d="M32 5 59 59H45.5L32 32.5 18.5 59H5Z" fill="currentColor"/></svg>
  <p>ATHLETIΞ — Plus fort chaque jour · <a href="<?= e(url('utilisateur/connexion.php')) ?>">Espace membre</a></p>
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
