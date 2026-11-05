  <dialog class="admin-reservation-dialog" id="reservation-dialogue">
    <button type="button" class="reservation-dialog-close" aria-label="Fermer">×</button>
    <form method="post" id="modifier-reservation-form">
      <?= champ_csrf() ?>
      <input type="hidden" name="action" value="accueil_modifier_reservation">
      <input type="hidden" name="reservation" id="dialog-reservation-id">
      <h3>Modifier la réservation</h3>
      <label for="dialog-reservation-course">Choisir un autre cours</label>
      <select name="activite" id="dialog-reservation-course" required>
        <option value="">Sélectionner un cours</option>
        <?php foreach ($activitesDisponiblesAccueil as $activiteOption):
          $coursComplet = (int) $activiteOption['places_prises'] >= (int) $activiteOption['capacite_max'];
        ?>
          <option value="<?= (int) $activiteOption['Numactivite'] ?>" data-complet="<?= $coursComplet ? '1' : '0' ?>" <?= $coursComplet ? 'disabled' : '' ?>>
            <?= e($activiteOption['titre']) ?> · <?= e(date('d/m/Y H:i', strtotime($activiteOption['date_heure_debut']))) ?> · <?= e($activiteOption['nomsalle']) ?> (<?= (int) $activiteOption['places_prises'] ?>/<?= (int) $activiteOption['capacite_max'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Enregistrer le changement</button>
    </form>
  </dialog>
