<section class="form" style="max-width:760px;margin:auto">
  <p class="mono">TECHNICIEN N° <?= (int) $technicienSelectionne['NumUsers'] ?></p>
  <h3>Modifier le compte</h3>
  <form method="post">
    <?= champ_csrf() ?>
    <input type="hidden" name="action" value="modifier_technicien">
    <input type="hidden" name="utilisateur" value="<?= (int) $technicienSelectionne['NumUsers'] ?>">
    <div class="form-ligne">
      <label for="nom">Nom</label>
      <input type="text" id="nom" name="nom" value="<?= e($technicienSelectionne['nomUsers']) ?>" required>
    </div>
    <div class="form-ligne">
      <label for="prenom">Prénom</label>
      <input type="text" id="prenom" name="prenom" value="<?= e($technicienSelectionne['prenomUsers']) ?>" required>
    </div>
    <div class="form-ligne">
      <label for="mail">E-mail</label>
      <input type="email" id="mail" name="mail" value="<?= e($technicienSelectionne['mailUsers']) ?>" required>
    </div>
    <div class="form-ligne">
      <label for="tel">Téléphone</label>
      <input type="text" id="tel" name="tel" value="<?= e($technicienSelectionne['telUsers'] ?? '') ?>">
    </div>
    <p style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
      <button class="btn btn-vert" type="submit">Enregistrer</button>
      <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=maintenance')) ?>">Annuler</a>
    </p>
  </form>
</section>
