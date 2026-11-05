  <section class="admin-reservations-page-content">
    <header class="admin-reservations-heading">
      <div class="admin-reservations-heading-copy">
        <span aria-hidden="true">▦</span>
        <div>
          <h2>Réservations des adhérents</h2>
          <p>Consultez toutes les réservations, puis modifiez ou annulez les séances à venir.</p>
        </div>
      </div>
    </header>

    <section class="admin-reservations-filters" aria-label="Filtres des réservations">
      <select id="filtre-reservation-activite" aria-label="Filtrer par activité">
        <option value="">Toutes les activités</option>
        <?php foreach (PlanningController::TYPES as $typeReservation => $libelleReservation): ?>
          <option value="<?= e($typeReservation) ?>"><?= e($libelleReservation) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="filtre-reservation-statut" aria-label="Filtrer par statut">
        <option value="">Tous les statuts</option>
        <option value="Reservee">Confirmées</option>
        <option value="En attente">En attente</option>
        <option value="Annulee">Annulées</option>
      </select>
      <label class="admin-reservations-search">
        <span aria-hidden="true">⌕</span>
        <input type="search" id="recherche-reservation" placeholder="Rechercher un adhérent ou un cours…" autocomplete="off">
      </label>
    </section>

<?php require __DIR__ . '/reservation-list.php'; ?>
<?php require __DIR__ . '/reservation-dialog.php'; ?>
<script src="<?= e(url('assets/js/employe/reservation-filters.js')) ?>"></script>
