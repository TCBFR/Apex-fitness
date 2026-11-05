
  <section class="maintenance-ticket-page<?= $maintenance ? ' is-technician' : '' ?>" aria-label="<?= $maintenance ? 'Mes tickets de maintenance' : 'Suivi de mes tickets' ?>">
    <header class="maintenance-ticket-page-heading">
      <div><?php if ($maintenance): ?><p class="ticket-page-eyebrow">ESPACE MAINTENANCE</p><?php endif; ?><h2><?= $maintenance ? 'Mes tickets' : 'Suivi des tickets' ?></h2><p><?= $maintenance ? 'Retrouvez la file des demandes et suivez l’avancement des interventions.' : 'Consultez l’état de vos demandes et suivez leur avancement.' ?></p></div>
    </header>
    <?php if (!$maintenance): ?>
    <div class="maintenance-ticket-summary">
      <article class="ticket-summary-card ticket-summary-total">
        <span class="ticket-summary-icon" aria-hidden="true"><i class="fa-solid fa-ticket"></i></span>
        <div><strong><?= count($ticketsPourSuivi) ?></strong><span><?= $maintenance ? 'Tickets dans la file' : 'Mes tickets' ?></span><small><?= $maintenance ? 'Toutes les demandes de maintenance' : 'Demandes signalées par vous' ?></small></div>
      </article>
      <article class="ticket-summary-card ticket-summary-progress">
        <span class="ticket-summary-icon" aria-hidden="true"><i class="fa-solid fa-wrench"></i></span>
        <div><strong><?= $ticketsEnCoursMaintenance ?></strong><span>En cours</span><small>Interventions actives</small></div>
      </article>
      <article class="ticket-summary-card ticket-summary-resolved">
        <span class="ticket-summary-icon" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
        <div><strong><?= $ticketsResoluMaintenance ?></strong><span>Résolus</span><small><?= $maintenance ? $ticketsResolusCetteSemaine . ' cette semaine' : 'Demandes clôturées' ?></small></div>
      </article>
      <article class="ticket-summary-card ticket-summary-waiting">
        <span class="ticket-summary-icon" aria-hidden="true"><i class="fa-regular fa-clock"></i></span>
        <div><strong><?= $ticketsEnAttenteMaintenance ?></strong><span>En attente</span><small><?= $maintenance ? 'À prendre en charge ou valider' : 'En attente d’intervention' ?></small></div>
      </article>
    </div>
    <?php endif; ?>

    <?php if (!$ticketsPourSuivi): ?>
      <div class="maintenance-ticket-empty">
        <span aria-hidden="true"><i class="fa-solid fa-ticket"></i></span>
        <h3><?= $maintenance ? 'Aucun ticket pour le moment' : 'Vous n’avez pas encore créé de ticket' ?></h3>
        <p><?= $maintenance ? 'Les demandes de maintenance apparaîtront ici dès leur création.' : 'Les tickets que vous signalez à l’équipe de maintenance apparaîtront ici.' ?></p>
        <?php if (!$maintenance): ?><a class="btn btn-vert" href="<?= e(url('employe/espace.php?onglet=creer-ticket')) ?>">Créer un ticket</a><?php endif; ?>
      </div>
    <?php else: ?>
      <div class="maintenance-ticket-workspace<?= $maintenance ? ' is-technician' . ($ticketSelectionne ? ' has-selection' : '') . ($vueEditionTicket ? ' is-editing' : '') : '' ?>">
        <?php if (!$vueEditionTicket): ?>
        <?php require __DIR__ . '/ticket-list.php'; ?>
        <?php endif; ?>

        <?php if ($ticketSelectionne): ?>
        <aside class="maintenance-ticket-detail-panel" data-ticket-detail-panel aria-label="Détails du ticket">
          <?php require __DIR__ . '/ticket-detail-overview.php'; ?>
          <?php require __DIR__ . '/ticket-detail-actions.php'; ?>
        </aside>
        <?php endif; ?>
      </div>

    <?php endif; ?>
  </section>
