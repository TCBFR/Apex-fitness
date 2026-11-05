    <div class="maintenance-dashboard-grid">
      <section class="maintenance-panel maintenance-tickets-panel" aria-labelledby="maintenance-tickets-title">
        <header class="maintenance-panel-heading">
          <h3 id="maintenance-tickets-title"><span aria-hidden="true">▤</span> Tickets de maintenance</h3>
          <a href="<?= e(url('employe/espace.php?onglet=maintenance')) ?>">Voir tous →</a>
        </header>
        <?php if (!$maintenances): ?>
          <p class="maintenance-empty">Aucun ticket de maintenance pour le moment.</p>
        <?php else: ?>
          <div class="maintenance-table-wrap">
            <table class="maintenance-table">
              <thead><tr><th>Ticket</th><th>Description</th><th>Équipement</th><th>Priorité</th><th>Statut</th><th>Signalé le</th></tr></thead>
              <tbody>
              <?php foreach (array_slice($maintenances, 0, 5) as $ticket): ?>
                <tr>
                  <td class="maintenance-ticket-number">TKT-<?= (int) $ticket['Nummaint'] ?></td>
                  <td><?= e($ticket['description_problememaint']) ?></td>
                  <td><?= e($ticket['nomequip']) ?></td>
                  <td><span class="maintenance-priority<?= $ticket['priorite'] === 'Haute' ? ' priority-high' : ($ticket['priorite'] === 'Moyenne' ? ' priority-medium' : '') ?>"><?= e($ticket['priorite']) ?></span></td>
                  <td><span class="maintenance-status<?= $ticket['libelle_statut'] === 'Terminee' ? ' status-done' : (in_array($ticket['libelle_statut'], ['Ouverte', 'En attente'], true) ? ' status-open' : ' status-progress') ?>"><?= $ticket['libelle_statut'] === 'Terminee' ? 'Terminée' : e($ticket['libelle_statut']) ?></span></td>
                  <td><?= e(dateFr($ticket['date_demandemaint'])) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <section class="maintenance-panel maintenance-equipment-panel" aria-labelledby="maintenance-equipment-title">
        <header class="maintenance-panel-heading">
          <h3 id="maintenance-equipment-title"><span aria-hidden="true">⬡</span> Équipements à contrôler</h3>
          <a href="<?= e(url('employe/espace.php?onglet=materiel')) ?>">Voir tout →</a>
        </header>
        <?php if (!$equipementsMaintenanceAControler): ?>
          <p class="maintenance-empty">Tous les équipements sont disponibles.</p>
        <?php else: ?>
          <ul class="maintenance-equipment-list">
            <?php foreach (array_slice($equipementsMaintenanceAControler, 0, 5) as $equipement): ?>
              <li>
                <span class="equipment-indicator" aria-hidden="true">⚙</span>
                <span class="equipment-name"><?= e($equipement['nomequip']) ?></span>
                <span class="maintenance-status<?= $equipement['libelle_statut'] === 'Hors service' ? ' status-open' : ' status-progress' ?>"><?= e($equipement['libelle_statut']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <a class="maintenance-panel-link" href="<?= e(url('employe/espace.php?onglet=materiel')) ?>">Consulter l’inventaire →</a>
      </section>

      <section class="maintenance-panel maintenance-activity-panel" aria-labelledby="maintenance-activity-title">
        <header class="maintenance-panel-heading">
          <h3 id="maintenance-activity-title"><span aria-hidden="true">◷</span> Activité récente</h3>
        </header>
        <?php if (!$activiteMaintenanceRecente): ?>
          <p class="maintenance-empty">Aucune intervention enregistrée.</p>
        <?php else: ?>
          <ol class="maintenance-activity-list">
            <?php foreach ($activiteMaintenanceRecente as $activite): ?>
              <li>
                <span class="activity-dot" aria-hidden="true"></span>
                <div>
                  <strong>Ticket TKT-<?= (int) $activite['Nummaint'] ?> — <?= e($activite['libelle_statut']) ?></strong>
                  <?php if (trim((string) $activite['commentaire']) !== ''): ?>
                    <p><?= e($activite['commentaire']) ?></p>
                  <?php endif; ?>
                  <small>par <?= e(trim($activite['prenomUsers'] . ' ' . $activite['nomUsers'])) ?> · <?= e(dateFr($activite['date_intervention'], true)) ?></small>
                </div>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
      </section>

      <section class="maintenance-panel maintenance-shortcuts-panel" aria-labelledby="maintenance-shortcuts-title">
        <header class="maintenance-panel-heading">
          <h3 id="maintenance-shortcuts-title"><span aria-hidden="true">ϟ</span> Raccourcis</h3>
        </header>
        <div class="maintenance-shortcuts">
          <a href="<?= e(url('employe/espace.php?onglet=maintenance')) ?>"><span aria-hidden="true">⚒</span>Voir mes tickets</a>
          <a href="<?= e(url('employe/espace.php?onglet=materiel')) ?>"><span aria-hidden="true">⬡</span>Gérer le matériel</a>
        </div>
      </section>
    </div>
  </section>
