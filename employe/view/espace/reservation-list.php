    <section class="admin-reservations-list" id="liste-reservations">
      <header>
        <h3>Liste des réservations <span>(<?= count($reservationsAccueil) ?>)</span></h3>
      </header>
      <div class="admin-reservation-table-scroll">
        <table class="admin-reservation-table">
          <thead>
            <tr><th>Date &amp; heure</th><th>Activité</th><th>Adhérent</th><th>Salle</th><th>Statut</th><th>Places</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php foreach ($reservationsAccueil as $reservation):
            $dateCoursReservation = new DateTimeImmutable($reservation['date_heure_debut']);
            $typeActiviteReservation = isset(PlanningController::TYPES[$reservation['type_activite']]) ? $reservation['type_activite'] : 'cardio';
            $nomAdherentReservation = trim($reservation['prenomUsers'] . ' ' . $reservation['nomUsers']);
            $nomCoachReservation = trim($reservation['coach_prenom'] . ' ' . $reservation['coach_nom']);
            $statutReservation = $reservation['statut_reservation'];
            $libelleStatutReservation = $statutReservation === 'Reservee' ? 'Confirmée' : ($statutReservation === 'Annulee' ? 'Annulée' : 'En attente');
            $reservationFuture = strtotime($reservation['date_heure_debut']) >= time();
            $motsRechercheReservation = mb_strtolower($nomAdherentReservation . ' ' . $reservation['mailUsers'] . ' ' . $reservation['titre'] . ' ' . $reservation['nomsalle'] . ' ' . $nomCoachReservation);
          ?>
            <tr data-reservation-row data-type="<?= e($typeActiviteReservation) ?>" data-status="<?= e($statutReservation) ?>" data-search="<?= e($motsRechercheReservation) ?>">
              <td>
                <strong><?= e($dateCoursReservation->format('d/m/Y')) ?></strong>
                <span><?= e($dateCoursReservation->format('H:i')) ?> – <?= e((new DateTimeImmutable($reservation['date_heure_fin']))->format('H:i')) ?></span>
              </td>
              <td><div><strong><?= e($reservation['titre']) ?></strong><span>Coach : <?= e($nomCoachReservation) ?></span></div></td>
              <td><div><strong><?= e($nomAdherentReservation) ?></strong><span><?= e($reservation['mailUsers']) ?></span></div></td>
              <td><?= e($reservation['nomsalle']) ?></td>
              <td><span class="admin-reservation-status status-<?= $statutReservation === 'Reservee' ? 'confirmed' : ($statutReservation === 'Annulee' ? 'cancelled' : 'waiting') ?>"><?= e($libelleStatutReservation) ?></span></td>
              <td><?= (int) $reservation['places_prises'] ?>/<?= (int) $reservation['capacite_max'] ?></td>
              <td>
                <div class="admin-reservation-actions">
                  <?php if ($statutReservation !== 'Annulee' && $reservationFuture): ?>
                    <button type="button" data-reservation-edit data-id="<?= (int) $reservation['id_ligne'] ?>" data-current-course="<?= (int) $reservation['Numactivite'] ?>" aria-label="Modifier la réservation de <?= e($nomAdherentReservation) ?>">Modifier</button>
                    <form method="post" class="admin-reservation-action-form">
                      <?= champ_csrf() ?>
                      <input type="hidden" name="action" value="accueil_annuler_reservation">
                      <input type="hidden" name="reservation" value="<?= (int) $reservation['id_ligne'] ?>">
                      <button type="submit" class="is-danger" data-confirme="Annuler la réservation de <?= e($nomAdherentReservation) ?> ?" aria-label="Annuler la réservation">Annuler</button>
                    </form>
                  <?php else: ?>
                    <span class="admin-reservation-no-action"><?= $statutReservation === 'Annulee' ? 'Aucune action' : 'Séance passée' ?></span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$reservationsAccueil): ?>
            <tr><td colspan="7" class="admin-reservation-no-data">Aucune réservation enregistrée.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <p class="admin-reservation-filter-empty" id="reservations-filtre-vide" hidden>Aucune réservation ne correspond aux filtres.</p>
    </section>
  </section>
