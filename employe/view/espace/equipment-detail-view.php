
              <header class="inventory-detail-heading">
                <a class="inventory-detail-back" href="<?= e(url('employe/espace.php?onglet=materiel')) ?>" aria-label="Retour à l’inventaire"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                <h3><i class="fa-solid fa-cube" aria-hidden="true"></i> Détails du matériel</h3>
                <a href="<?= e(url('employe/espace.php?onglet=materiel')) ?>" aria-label="Fermer le détail"><i class="fa-solid fa-xmark" aria-hidden="true"></i></a>
              </header>
              <div class="inventory-detail-product">
                <?php if (!empty($materielSelectionne['image_equipement'])): ?>
                  <img class="inventory-detail-product-image" src="<?= e(url($materielSelectionne['image_equipement'])) ?>" alt="">
                <?php else: ?>
                <span class="inventory-detail-product-icon" aria-hidden="true"><i class="fa-solid fa-cube"></i></span>
                <?php endif; ?>
                <div><p>N° <?= (int) $materielSelectionne['Numequip'] ?></p><h4><?= e($materielSelectionne['nomequip']) ?></h4><small><?= e(trim(($materielSelectionne['marque'] ?? '') . ' ' . ($materielSelectionne['modele'] ?? '')) ?: 'Modèle non renseigné') ?></small></div>
              </div>
              <span class="inventory-stock-badge<?= (int) $materielSelectionne['stock_actuel'] <= (int) $materielSelectionne['seuil_alerte'] ? ' is-low' : '' ?>"><i class="fa-solid fa-box" aria-hidden="true"></i><?= (int) $materielSelectionne['stock_actuel'] === 0 ? 'Rupture de stock' : ((int) $materielSelectionne['stock_actuel'] <= (int) $materielSelectionne['seuil_alerte'] ? 'Stock faible' : 'En stock') ?></span>
              <span class="inventory-status is-<?= e($statusClass) ?>"><i class="fa-solid fa-circle" aria-hidden="true"></i><?= e($materielSelectionne['libelle_statut']) ?></span>
              <dl class="inventory-detail-facts">
                <div><dt><i class="fa-solid fa-cube" aria-hidden="true"></i></dt><dd>Catégorie : <?= e($materielSelectionne['libelletypeequip']) ?></dd></div>
                <div><dt><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i></dt><dd>Stock actuel : <strong><?= (int) $materielSelectionne['stock_actuel'] ?> unité<?= (int) $materielSelectionne['stock_actuel'] === 1 ? '' : 's' ?></strong></dd></div>
                <div><dt><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></dt><dd>Seuil d’alerte : <strong><?= (int) $materielSelectionne['seuil_alerte'] ?> unité<?= (int) $materielSelectionne['seuil_alerte'] === 1 ? '' : 's' ?></strong></dd></div>
                <div><dt><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i></dt><dd>Fournisseur : <?= e($materielSelectionne['nomfourn'] ?: 'Non renseigné') ?></dd></div>
                <div><dt><i class="fa-regular fa-calendar" aria-hidden="true"></i></dt><dd>Dernière entrée : <?= $derniereEntreeMateriel ? dateFr($derniereEntreeMateriel) : 'Aucune entrée enregistrée' ?></dd></div>
                <div><dt><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i></dt><dd>Dernière maintenance : <?= dateFr($materielSelectionne['date_derniere_maintenance']) ?></dd></div>
              </dl>
              <section class="inventory-movement-history" aria-labelledby="inventory-movement-history-title">
                <header>
                  <h4 id="inventory-movement-history-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Historique des mouvements</h4>
                  <span><?= $nombreMouvementsMateriel ?></span>
                  <?php if ($nombreMouvementsMateriel > 5): ?><a href="<?= e(url('employe/espace.php?onglet=materiel&materiel=' . (int) $materielSelectionne['Numequip'] . ($historiqueMaterielComplet ? '' : '&historique=1'))) ?>"><?= $historiqueMaterielComplet ? 'Voir moins' : 'Voir tout' ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a><?php endif; ?>
                </header>
                <?php if (!$mouvementsMateriel): ?>
                  <p>Aucun mouvement de stock enregistré.</p>
                <?php else: ?>
                  <ul>
                    <?php foreach ($mouvementsMateriel as $mouvement): ?>
                      <li class="<?= $mouvement['type_mouvement'] === 'ENTREE' ? 'is-entry' : 'is-exit' ?>">
                        <i class="fa-solid <?= $mouvement['type_mouvement'] === 'ENTREE' ? 'fa-arrow-up' : 'fa-arrow-down' ?>" aria-hidden="true"></i>
                        <span><strong><?= $mouvement['type_mouvement'] === 'ENTREE' ? 'Entrée de stock' : 'Sortie (utilisation)' ?></strong><small><?= e($mouvement['commentaire'] ?: $mouvement['prenomUsers'] . ' ' . $mouvement['nomUsers']) ?></small></span>
                        <b><?= $mouvement['type_mouvement'] === 'ENTREE' ? '+' : '−' ?><?= (int) $mouvement['quantite'] ?></b>
                        <time datetime="<?= e(date('c', strtotime($mouvement['date_mouvement']))) ?>"><?= dateFr($mouvement['date_mouvement']) ?></time>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </section>
              <details class="inventory-use-panel">
                <summary><i class="fa-solid fa-cube" aria-hidden="true"></i> Déclarer une utilisation</summary>
                <form method="post">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="declarer_utilisation_materiel">
                  <input type="hidden" name="materiel" value="<?= (int) $materielSelectionne['Numequip'] ?>">
                  <label>Quantité utilisée<input type="number" name="quantite" min="1" max="<?= (int) $materielSelectionne['stock_actuel'] ?>" required></label>
                  <label>Commentaire facultatif<input type="text" name="commentaire" maxlength="255" placeholder="Ex. matériel sorti pour intervention"></label>
                  <button type="submit"<?= (int) $materielSelectionne['stock_actuel'] < 1 ? ' disabled' : '' ?>>Enregistrer la sortie</button>
                </form>
              </details>

              <section class="inventory-ticket-history" aria-labelledby="inventory-ticket-history-title">
                <header><h4 id="inventory-ticket-history-title"><i class="fa-regular fa-clock" aria-hidden="true"></i> Historique maintenance</h4><span><?= count($ticketsEquipement) ?></span></header>
                <?php if (!$ticketsEquipement): ?>
                  <p>Aucun ticket de maintenance enregistré.</p>
                <?php else: ?>
                  <ul>
                    <?php foreach (array_slice($ticketsEquipement, 0, 3) as $ticket): ?>
                      <li><span><strong>TKT-<?= (int) $ticket['Nummaint'] ?></strong><small><?= e($ticket['libelle_statut']) ?> · <?= dateFr($ticket['date_demandemaint']) ?></small></span><a href="<?= e(url('employe/espace.php?onglet=' . ($accueil ? 'suivi-tickets' : 'maintenance') . '&ticket=' . (int) $ticket['Nummaint'])) ?>">Voir</a></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </section>
