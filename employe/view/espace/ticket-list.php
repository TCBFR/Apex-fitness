        <section class="maintenance-ticket-list-panel" aria-label="Liste des tickets">
          <div class="ticket-toolbar">
            <div class="ticket-status-tabs" role="group" aria-label="Filtrer par statut">
              <button type="button" class="ticket-tab is-active" data-status-filter="all" aria-pressed="true"><i class="fa-solid fa-ticket" aria-hidden="true"></i> Tous <span><?= count($ticketsPourSuivi) ?></span></button>
              <button type="button" class="ticket-tab" data-status-filter="progress" aria-pressed="false"><i class="fa-regular fa-clock" aria-hidden="true"></i> En cours <span><?= $ticketsEnCoursMaintenance ?></span></button>
              <button type="button" class="ticket-tab" data-status-filter="resolved" aria-pressed="false"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Résolus <span><?= $ticketsResoluMaintenance ?></span></button>
              <button type="button" class="ticket-tab" data-status-filter="waiting" aria-pressed="false"><i class="fa-regular fa-hourglass-half" aria-hidden="true"></i> En attente <span><?= $ticketsEnAttenteMaintenance ?></span></button>
            </div>
            <div class="ticket-filters">
              <label class="ticket-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" data-ticket-search placeholder="Rechercher un ticket…" aria-label="Rechercher un ticket"></label>
              <label class="ticket-category-filter"><span class="sr-only">Filtrer par catégorie</span><select data-ticket-category><option value="">Toutes catégories</option><?php foreach ($categoriesTickets as $categorieTicket): ?><option value="<?= e($categorieTicket) ?>"><?= e($categorieTicket) ?></option><?php endforeach; ?></select></label>
              <label class="ticket-priority-filter"><span class="sr-only">Filtrer par priorité</span><select data-ticket-priority><option value="">Toutes priorités</option><?php foreach ($prioritesTickets as $prioriteTicket): ?><option value="<?= e($prioriteTicket) ?>"><?= e($prioriteTicket) ?></option><?php endforeach; ?></select></label>
            </div>
          </div>

          <div class="ticket-table-scroll">
            <table class="maintenance-ticket-table">
              <thead><tr><th># Ticket</th><th>Objet</th><th>Catégorie</th><th>Statut</th><th>Priorité</th><th>Date</th><th>Actions</th></tr></thead>
              <tbody>
              <?php foreach ($ticketsPourSuivi as $index => $ticket):
                $statusGroup = $ticket['libelle_statut'] === 'Terminee'
                  ? 'resolved'
                  : ($ticket['libelle_statut'] === 'En cours' ? 'progress' : 'waiting');
                $statusClass = match ($ticket['libelle_statut']) {
                  'Terminee' => 'resolved',
                  'En cours' => 'progress',
                  'À valider' => 'validation',
                  'En attente de piece' => 'parts',
                  default => 'waiting',
                };
                $ticketSearchText = implode(' ', [
                  'TKT-' . (int) $ticket['Nummaint'],
                  $ticket['description_problememaint'],
                  $ticket['nomequip'],
                  $ticket['libelletypeequip'],
                  $ticket['prenomUsers'] . ' ' . $ticket['nomUsers'],
                  $ticket['priorite'],
                  $ticket['libelle_statut'],
                  $ticket['technicien_prenom'] ?? '',
                  $ticket['technicien_nom'] ?? '',
                ]);
                $isSelected = $ticketSelectionne !== null
                  && (int) $ticket['Nummaint'] === (int) $ticketSelectionne['Nummaint'];
                ?>
                <tr data-ticket-row data-ticket-id="<?= (int) $ticket['Nummaint'] ?>" data-status-group="<?= e($statusGroup) ?>" data-category="<?= e($ticket['libelletypeequip']) ?>" data-priority="<?= e($ticket['priorite']) ?>" data-search="<?= e(mb_strtolower($ticketSearchText)) ?>"<?= $isSelected ? ' class="is-selected"' : '' ?>>
                  <td class="ticket-row-id">TKT-<?= (int) $ticket['Nummaint'] ?></td>
                  <td class="ticket-row-subject" title="<?= e($ticket['description_problememaint']) ?>"><?= e($ticket['description_problememaint']) ?><small><?= e($ticket['nomequip']) ?></small></td>
                  <td><?= e($ticket['libelletypeequip']) ?></td>
                  <td><span class="ticket-status-badge is-<?= e($statusClass) ?>"><?= $ticket['libelle_statut'] === 'Terminee' ? 'Résolu' : e($ticket['libelle_statut']) ?></span></td>
                  <td><span class="ticket-priority-badge is-<?= e(mb_strtolower($ticket['priorite'])) ?>"><?= e($ticket['priorite']) ?></span></td>
                  <td class="ticket-row-date"><?= dateFr($ticket['date_demandemaint'], true) ?></td>
                  <td><div class="ticket-row-actions">
                    <a class="ticket-view-button" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets . '&ticket=' . (int) $ticket['Nummaint'])) ?>"<?= $isSelected ? ' aria-current="true"' : '' ?>><i class="fa-regular fa-eye" aria-hidden="true"></i> Voir</a>
                    <?php if ($maintenance): ?><a class="ticket-view-button ticket-row-edit" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets . '&ticket=' . (int) $ticket['Nummaint'] . '&vue_ticket=modifier')) ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i> Modifier</a><?php endif; ?>
                  </div></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <p class="ticket-no-results" data-ticket-no-results hidden>Aucun ticket ne correspond à ces filtres.</p>
          </div>
          <footer class="ticket-table-footer">
            <span data-ticket-range></span>
            <nav class="ticket-pagination" aria-label="Pagination des tickets">
              <button type="button" data-ticket-prev aria-label="Page précédente"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
              <span data-ticket-pages></span>
              <button type="button" data-ticket-next aria-label="Page suivante"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
            </nav>
          </footer>
        </section>
