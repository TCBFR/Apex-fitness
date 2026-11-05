<div class="table-scroll">
  <table class="tableau">
    <thead><tr><th>Nom</th><th>E-mail</th><th>Fonction</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($personnel as $employe): ?>
      <tr>
        <td><?= e($employe['prenomUsers'] . ' ' . $employe['nomUsers']) ?><br><span class="mono"><?= e($employe['matriculeemp']) ?></span></td>
        <td><?= e($employe['mailUsers']) ?></td>
        <td>
          <form method="post" style="display:flex;gap:8px;margin:0">
            <?= champ_csrf() ?>
            <input type="hidden" name="action" value="fonction_modifier">
            <input type="hidden" name="utilisateur" value="<?= (int) $employe['NumUsers'] ?>">
            <select name="fonction">
              <?php foreach ($fonctions as $fonction): ?>
                <option value="<?= (int) $fonction['Numfonc'] ?>" <?= (int) $fonction['Numfonc'] === (int) $employe['Numfonc'] ? 'selected' : '' ?>><?= e($fonction['libellefonc']) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-ghost" type="submit">Modifier</button>
          </form>
        </td>
        <td>
          <form method="post" style="margin:0">
            <?= champ_csrf() ?>
            <input type="hidden" name="action" value="employe_supprimer">
            <input type="hidden" name="utilisateur" value="<?= (int) $employe['NumUsers'] ?>">
            <button class="btn btn-sm btn-danger" type="submit" data-confirme="Retirer cet employé du personnel ?">Retirer</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p class="note">Les fonctions disponibles sont celles du modèle: coach sportif, accueil, maintenance et responsable.</p>
