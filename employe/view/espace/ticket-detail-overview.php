
            <?php
            $ticket = $ticketSelectionne;
            $statusClass = match ($ticket['libelle_statut']) {
              'Terminee' => 'resolved',
              'En cours' => 'progress',
              'À valider' => 'validation',
              'En attente de piece' => 'parts',
              default => 'waiting',
            };
            ?>
            <header class="ticket-detail-heading">
              <?php if ($vueEditionTicket): ?>
                <a class="ticket-detail-back" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets . '&ticket=' . (int) $ticket['Nummaint'])) ?>" aria-label="Retour au détail du ticket"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                <h3><i class="fa-solid fa-pen" aria-hidden="true"></i> Modifier le ticket</h3>
                <a class="ticket-detail-edit" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets . '&ticket=' . (int) $ticket['Nummaint'])) ?>"><i class="fa-regular fa-eye" aria-hidden="true"></i> Voir</a>
              <?php else: ?>
                <a class="ticket-detail-back" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets)) ?>" aria-label="Retour à la liste des tickets"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                <h3><i class="fa-solid fa-ticket" aria-hidden="true"></i> Détails du ticket</h3>
                <?php if ($maintenance): ?>
                <a class="ticket-detail-edit" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets . '&ticket=' . (int) $ticket['Nummaint'] . '&vue_ticket=modifier')) ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i> Modifier</a>
                <?php endif; ?>
              <?php endif; ?>
            </header>
            <?php if ($vueEditionTicket): ?>
            <div class="ticket-edit-summary">
              <p class="ticket-detail-id">TKT-<?= (int) $ticket['Nummaint'] ?></p>
              <h4><?= e($ticket['description_problememaint']) ?></h4>
              <p><i class="fa-solid fa-cube" aria-hidden="true"></i> <?= e($ticket['nomequip']) ?> · <?= e($ticket['libelletypeequip']) ?></p>
              <span class="ticket-status-badge is-<?= e($statusClass) ?>"><?= $ticket['libelle_statut'] === 'Terminee' ? 'Résolu' : e($ticket['libelle_statut']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!$vueEditionTicket): ?>
            <div class="ticket-detail-title-row">
              <p class="ticket-detail-id">TKT-<?= (int) $ticket['Nummaint'] ?></p>
              <span class="ticket-priority-badge is-<?= e(mb_strtolower($ticket['priorite'])) ?>"><?= e($ticket['priorite']) ?> priorité</span>
            </div>
            <h4 class="ticket-detail-subject"><?= e($ticket['description_problememaint']) ?></h4>
            <?php
            $dateDebutIntervention = null;
            $dateFinIntervention = null;
            foreach ($interventionsTicket as $interventionTicket) {
              if ($dateDebutIntervention === null && in_array($interventionTicket['libelle_statut'], ['En cours', 'En attente de piece', 'À valider', 'Terminee'], true)) {
                $dateDebutIntervention = $interventionTicket['date_intervention'];
              }
              if (in_array($interventionTicket['libelle_statut'], ['À valider', 'Terminee'], true)) {
                $dateFinIntervention = $interventionTicket['date_intervention'];
              }
            }
            $ticketResolu = $ticket['libelle_statut'] === 'Terminee';
            $interventionDemarree = $dateDebutIntervention !== null || in_array($ticket['libelle_statut'], ['En cours', 'En attente de piece', 'À valider', 'Terminee'], true);
            ?>
            <ol class="ticket-progress-steps" aria-label="Avancement du ticket">
              <li class="is-complete"><span><i class="fa-solid fa-check" aria-hidden="true"></i></span><strong>Créé</strong><small><?= dateFr($ticket['date_demandemaint'], true) ?></small></li>
              <li class="<?= $interventionDemarree ? 'is-complete' : '' ?>"><span><?= $interventionDemarree ? '<i class="fa-solid fa-check" aria-hidden="true"></i>' : '2' ?></span><strong><?= $interventionDemarree ? 'En cours' : 'En attente' ?></strong><small><?= $dateDebutIntervention ? dateFr($dateDebutIntervention, true) : 'Non renseignée' ?></small></li>
              <li class="<?= $ticketResolu ? 'is-complete' : '' ?>"><span><?= $ticketResolu ? '<i class="fa-solid fa-check" aria-hidden="true"></i>' : '3' ?></span><strong><?= $ticketResolu ? 'Résolu' : 'Résolution' ?></strong><small><?= $dateFinIntervention ? dateFr($dateFinIntervention, true) : 'Non renseignée' ?></small></li>
            </ol>
            <dl class="ticket-detail-facts">
              <div><dt><i class="fa-solid fa-wrench" aria-hidden="true"></i></dt><dd>Catégorie : <?= e($ticket['libelletypeequip']) ?></dd></div>
              <div><dt><i class="fa-solid fa-cube" aria-hidden="true"></i></dt><dd>Équipement : <?= e($ticket['nomequip']) ?> <small>(<?= e($ticket['num_inventaire']) ?>)</small></dd></div>
              <div><dt><i class="fa-solid fa-user" aria-hidden="true"></i></dt><dd>Demandeur : <?= e($ticket['prenomUsers'] . ' ' . $ticket['nomUsers']) ?></dd></div>
              <div class="ticket-detail-description"><dt><i class="fa-solid fa-file-lines" aria-hidden="true"></i></dt><dd>Description<p><?= nl2br(e($ticket['description_problememaint'])) ?></p></dd></div>
            </dl>
            <div class="ticket-detail-assignment">
              <h4><i class="fa-solid fa-wrench" aria-hidden="true"></i> Intervention</h4>
              <p><i class="fa-solid fa-user-gear" aria-hidden="true"></i> Technicien assigné :
                <?php if (!$ticket['technicien_nom']): ?>File commune
                <?php elseif ((int) $ticket['NumUsers_technicien'] === (int) $moi): ?>Vous
                <?php else: ?><?= e($ticket['technicien_prenom'] . ' ' . $ticket['technicien_nom']) ?><?php endif; ?>
              </p>
            </div>
            <?php endif; ?>
