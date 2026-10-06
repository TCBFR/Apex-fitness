<div class="compteurs">
  <div class="compteur">
    <div class="v"><?= count($techniciensMaintenance) ?></div>
    <div class="k">techniciens de maintenance</div>
  </div>
</div>

<div class="barre-outils">
  <a class="btn btn-vert" href="<?= e(url('admin/espace.php?onglet=maintenance&maintenance=creer')) ?>">
    Créer un technicien
  </a>
</div>

<?php if (!$techniciensMaintenance): ?>
  <div class="vide">Aucun technicien de maintenance n’est enregistré.</div>
<?php else: ?>
  <div class="table-scroll">
    <table class="tableau">
      <thead>
        <tr><th>Nom</th><th>E-mail</th><th>Matricule</th><th>Embauché le</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($techniciensMaintenance as $technicien): ?>
          <tr>
            <td><?= e($technicien['prenomUsers'] . ' ' . $technicien['nomUsers']) ?></td>
            <td><?= e($technicien['mailUsers']) ?></td>
            <td><?= e($technicien['matriculeemp'] ?? '') ?></td>
            <td><?= e(dateFr($technicien['Date_embaucheemp'] ?? '')) ?></td>
            <td style="display:flex;gap:8px;flex-wrap:wrap">
              <a class="btn btn-sm btn-ghost" href="<?= e(url('admin/espace.php?onglet=maintenance&maintenance=voir&id=' . (int) $technicien['NumUsers'])) ?>">Voir</a>
              <a class="btn btn-sm btn-ghost" href="<?= e(url('admin/espace.php?onglet=maintenance&maintenance=modifier&id=' . (int) $technicien['NumUsers'])) ?>">Modifier</a>
              <form method="post">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="supprimer">
                <input type="hidden" name="utilisateur" value="<?= (int) $technicien['NumUsers'] ?>">
                <input type="hidden" name="retour" value="maintenance">
                <button class="btn btn-sm btn-danger" type="submit" data-confirme="Supprimer définitivement ce compte ?">Supprimer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
