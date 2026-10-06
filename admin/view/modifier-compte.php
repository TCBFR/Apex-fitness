<section class="form" style="max-width:760px;margin:auto">
  <p class="mono">COMPTE N° <?= (int) $compteModifie['NumUsers'] ?></p>
  <h2>Modifier un compte</h2>
  <form method="post">
    <?= champ_csrf() ?>
    <input type="hidden" name="action" value="modifier_utilisateur">
    <input type="hidden" name="utilisateur" value="<?= (int) $compteModifie['NumUsers'] ?>">

    <div class="form-ligne">
      <label for="nom">Nom</label>
      <input type="text" id="nom" name="nom" value="<?= e($compteModifie['nomUsers']) ?>" required>
    </div>
    <div class="form-ligne">
      <label for="prenom">Prénom</label>
      <input type="text" id="prenom" name="prenom" value="<?= e($compteModifie['prenomUsers']) ?>" required>
    </div>
    <div class="form-ligne">
      <label for="mail">E-mail</label>
      <input type="email" id="mail" name="mail" value="<?= e($compteModifie['mailUsers']) ?>" required>
    </div>
    <div class="form-ligne">
      <label for="tel">Téléphone</label>
      <input type="text" id="tel" name="tel" value="<?= e($compteModifie['telUsers'] ?? '') ?>">
    </div>

    <p style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
      <button class="btn btn-vert" type="submit">Enregistrer</button>
      <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=utilisateurs')) ?>">Annuler</a>
    </p>
  </form>
</section>
