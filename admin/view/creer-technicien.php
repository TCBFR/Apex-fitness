<section class="form" style="max-width:760px;margin:auto">
  <p class="mono">ÉQUIPE TECHNIQUE</p>
  <h3>Créer un technicien de maintenance</h3>

  <?php if ($fonctionMaintenance === null): ?>
    <p class="flash flash-erreur">Aucune fonction contenant « maintenance » n’existe dans la liste des fonctions.</p>
    <p><a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=maintenance')) ?>">Retour à la liste</a></p>
  <?php else: ?>
    <p class="muted">La fonction « <?= e($fonctionMaintenance['libellefonc']) ?> » sera attribuée automatiquement.</p>
    <form method="post">
      <?= champ_csrf() ?>
      <input type="hidden" name="action" value="creer_technicien">
      <div class="form-ligne">
        <label for="nom">Nom</label>
        <input type="text" id="nom" name="nom" required>
      </div>
      <div class="form-ligne">
        <label for="prenom">Prénom</label>
        <input type="text" id="prenom" name="prenom" required>
      </div>
      <div class="form-ligne">
        <label for="mail">E-mail</label>
        <input type="email" id="mail" name="mail" required>
      </div>
      <div class="form-ligne">
        <label for="tel">Téléphone</label>
        <input type="text" id="tel" name="tel">
      </div>
      <div class="form-ligne">
        <label for="motdepasse">Mot de passe</label>
        <input type="password" id="motdepasse" name="motdepasse" minlength="8" required>
      </div>
      <p style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
        <button class="btn btn-vert" type="submit">Créer le compte</button>
        <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=maintenance')) ?>">Annuler</a>
      </p>
    </form>
  <?php endif; ?>
</section>
