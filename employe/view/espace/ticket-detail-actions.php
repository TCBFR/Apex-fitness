            <?php if ($maintenance && $vueEditionTicket): ?>
            <div class="ticket-detail-actions" id="ticket-detail-actions">
              <h4>Actions de maintenance</h4>
              <?php if (
                (int) $ticket['NumUsers_technicien'] === (int) $moi
                && !in_array($ticket['libelle_statut'], ['Terminee', 'À valider'], true)
              ): ?>
                <form class="ticket-comment-form" method="post">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="commenter_ticket">
                  <input type="hidden" name="maintenance" value="<?= (int) $ticket['Nummaint'] ?>">
                  <label class="sr-only" for="ticket-new-comment-<?= (int) $ticket['Nummaint'] ?>">Ajouter un commentaire</label>
                  <textarea id="ticket-new-comment-<?= (int) $ticket['Nummaint'] ?>" name="commentaire" rows="1" maxlength="2000" placeholder="Ajouter un commentaire…" required></textarea>
                  <button type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>Envoyer</span></button>
                </form>
              <?php endif; ?>
              <?php if ($ticket['NumUsers_technicien'] === null && $ticket['libelle_statut'] === 'Ouverte'): ?>
                <form method="post">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="prendre_ticket">
                  <input type="hidden" name="maintenance" value="<?= (int) $ticket['Nummaint'] ?>">
                  <button class="ticket-action-primary" type="submit"><i class="fa-solid fa-hand" aria-hidden="true"></i> Prendre en charge</button>
                </form>
              <?php elseif (
                (int) $ticket['NumUsers_technicien'] === (int) $moi
                && !in_array($ticket['libelle_statut'], ['Terminee', 'À valider'], true)
              ): ?>
                <form method="post" class="ticket-intervention-form">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="avancer_maintenance">
                  <input type="hidden" name="maintenance" value="<?= (int) $ticket['Nummaint'] ?>">
                  <label for="ticket-status-<?= (int) $ticket['Nummaint'] ?>">Mettre à jour le statut</label>
                  <select id="ticket-status-<?= (int) $ticket['Nummaint'] ?>" name="statut" required>
                    <?php foreach ($statutsMaint as $statut):
                      if (!in_array($statut['libelle_statut'], $transitionsMaintenance[$ticket['libelle_statut']] ?? [], true)) continue; ?>
                      <option value="<?= (int) $statut['Numstatut'] ?>"><?= e($statut['libelle_statut']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <label for="ticket-comment-<?= (int) $ticket['Nummaint'] ?>">Note d’intervention</label>
                  <textarea id="ticket-comment-<?= (int) $ticket['Nummaint'] ?>" name="commentaire" rows="3" placeholder="Ajouter une note ou un compte rendu…"></textarea>
                  <button class="ticket-action-primary" type="submit"><i class="fa-solid fa-check" aria-hidden="true"></i> Enregistrer l’intervention</button>
                </form>
                <form method="post">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="detacher_ticket">
                  <input type="hidden" name="maintenance" value="<?= (int) $ticket['Nummaint'] ?>">
                  <button class="ticket-action-secondary" type="submit" data-confirme="Remettre ce ticket dans la file commune pour qu’un autre technicien le prenne ?"><i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i> Remettre dans la file commune</button>
                </form>
              <?php elseif ($ticket['libelle_statut'] === 'À valider'): ?>
                <p class="ticket-action-notice">Intervention terminée, en attente de validation par le demandeur.</p>
              <?php elseif ($ticket['libelle_statut'] === 'Terminee'): ?>
                <p class="ticket-action-notice is-resolved">Ce ticket est résolu.</p>
              <?php else: ?>
                <p class="ticket-action-notice">Ce ticket est pris en charge par <?= e(trim(($ticket['technicien_prenom'] ?? '') . ' ' . ($ticket['technicien_nom'] ?? ''))) ?>.</p>
              <?php endif; ?>
              <a class="ticket-action-secondary" href="<?= e(url('employe/espace.php?onglet=materiel')) ?>"><i class="fa-solid fa-cube" aria-hidden="true"></i> Consulter le matériel</a>
            </div>
            <?php elseif (!$maintenance): ?>
            <div class="ticket-detail-actions">
              <h4>Suivi de la demande</h4>
              <?php if ($ticket['libelle_statut'] === 'À valider'): ?>
                <p class="ticket-action-notice">L’intervention est terminée. Confirmez sa réalisation pour clôturer votre ticket.</p>
                <form method="post">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="fermer_ticket">
                  <input type="hidden" name="maintenance" value="<?= (int) $ticket['Nummaint'] ?>">
                  <button class="ticket-action-primary" type="submit" data-confirme="Confirmer que l’intervention est satisfaisante et fermer ce ticket ?"><i class="fa-solid fa-check" aria-hidden="true"></i> Confirmer et fermer</button>
                </form>
              <?php elseif ($ticket['libelle_statut'] === 'Terminee'): ?>
                <p class="ticket-action-notice is-resolved">Votre ticket est résolu.</p>
              <?php elseif ($ticket['libelle_statut'] === 'En cours'): ?>
                <p class="ticket-action-notice">Un technicien intervient actuellement sur votre demande.</p>
              <?php else: ?>
                <p class="ticket-action-notice">Votre demande est enregistrée et suivie par l’équipe de maintenance.</p>
              <?php endif; ?>
              <a class="ticket-action-secondary" href="<?= e(url('employe/espace.php?onglet=creer-ticket')) ?>"><i class="fa-solid fa-ticket" aria-hidden="true"></i> Signaler un autre problème</a>
            </div>
            <?php endif; ?>
