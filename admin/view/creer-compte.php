<section class="form" style="max-width:760px;margin:auto">
  <p class="mono">GESTION DES UTILISATEURS</p>
  <h2>Créer un compte</h2>
  <form method="post">
    <?= champ_csrf() ?>
    <input type="hidden" name="action" value="creer_utilisateur">

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
    <div class="form-ligne">
      <label for="fonction">Fonction de l’employé</label>
      <select id="fonction" name="fonction">
        <?php foreach ($fonctions as $fonction): ?>
          <option value="<?= (int) $fonction['Numfonc'] ?>"><?= e($fonction['libellefonc']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <fieldset>
      <legend>Rôles du compte</legend>
      <?php foreach (['ADHERENT' => 'Adhérent', 'EMPLOYE' => 'Employé', 'ADMIN' => 'Administrateur'] as $role => $label): ?>
        <label style="font-weight:400">
          <input type="checkbox" name="roles[]" value="<?= e($role) ?>" style="width:auto">
          <?= e($label) ?>
        </label>
      <?php endforeach; ?>
    </fieldset>

    <p style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
      <button class="btn btn-vert" type="submit">Créer le compte</button>
      <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=utilisateurs')) ?>">Annuler</a>
    </p>
  </form>
</section>
