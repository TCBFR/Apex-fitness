          <section class="inventory-list-panel" aria-label="Inventaire des équipements">
            <div class="inventory-toolbar">
              <label class="inventory-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" data-inventory-search placeholder="Rechercher un matériel…" aria-label="Rechercher un matériel"></label>
              <label class="inventory-filter"><span class="sr-only">Filtrer par catégorie</span><select data-inventory-category><option value="">Toutes les catégories</option><?php foreach ($typesEquipement as $type): ?><option value="<?= e($type['libelletypeequip']) ?>"><?= e($type['libelletypeequip']) ?></option><?php endforeach; ?></select></label>
              <label class="inventory-filter"><span class="sr-only">Filtrer par statut</span><select data-inventory-status><option value="">Tous les statuts</option><option value="Disponible">Disponible</option><option value="Maintenance">Maintenance</option><option value="Hors service">Hors service</option></select></label>
              <button type="button" class="inventory-reset" data-inventory-reset><i class="fa-solid fa-filter" aria-hidden="true"></i> Réinitialiser</button>
            </div>
            <div class="inventory-table-scroll">
              <table class="inventory-table">
                <thead><tr><th>Matériel</th><th>Catégorie</th><th>Stock actuel</th><th>État</th><th>Dernière maintenance</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($equipements as $equipement):
                  $statusClass = match ($equipement['libelle_statut']) {
                    'Disponible' => 'available',
                    'Maintenance' => 'maintenance',
                    default => 'out',
                  };
                  $searchText = implode(' ', [
                    $equipement['Numequip'],
                    $equipement['nomequip'],
                    $equipement['marque'] ?? '',
                    $equipement['modele'] ?? '',
                    $equipement['libelletypeequip'],
                    $equipement['libelle_statut'],
                    $equipement['nomfourn'] ?? '',
                  ]);
                  $isSelected = $materielSelectionne !== null
                    && (int) $equipement['Numequip'] === (int) $materielSelectionne['Numequip'];
                  ?>
                  <tr data-inventory-row data-inventory-id="<?= (int) $equipement['Numequip'] ?>" data-category="<?= e($equipement['libelletypeequip']) ?>" data-status="<?= e($equipement['libelle_statut']) ?>" data-search="<?= e(mb_strtolower($searchText)) ?>"<?= $isSelected ? ' class="is-selected"' : '' ?>>
                    <td class="inventory-product">
                      <?php if (!empty($equipement['image_equipement'])): ?>
                        <img class="inventory-product-image" src="<?= e(url($equipement['image_equipement'])) ?>" alt="">
                      <?php else: ?>
                        <span class="inventory-product-icon" aria-hidden="true"><i class="fa-solid fa-cube"></i></span>
                      <?php endif; ?>
                      <span><strong><?= e($equipement['nomequip']) ?></strong><small><?= e(trim(($equipement['marque'] ?? '') . ' ' . ($equipement['modele'] ?? '')) ?: 'Modèle non renseigné') ?></small></span>
                    </td>
                    <td><span class="inventory-category"><?= e($equipement['libelletypeequip']) ?></span></td>
                    <td><span class="inventory-stock-count<?= (int) $equipement['stock_actuel'] <= (int) $equipement['seuil_alerte'] ? ' is-low' : '' ?>"><?= (int) $equipement['stock_actuel'] ?> unité<?= (int) $equipement['stock_actuel'] === 1 ? '' : 's' ?></span><small class="inventory-stock-threshold">Seuil : <?= (int) $equipement['seuil_alerte'] ?></small></td>
                    <td><span class="inventory-status is-<?= e($statusClass) ?>"><i class="fa-solid fa-circle" aria-hidden="true"></i><?= e($equipement['libelle_statut']) ?></span></td>
                    <td class="inventory-date"><?= dateFr($equipement['date_derniere_maintenance']) ?></td>
                    <td>
                      <div class="inventory-actions">
                        <a class="inventory-view-button" href="<?= e(url('employe/espace.php?onglet=materiel&materiel=' . (int) $equipement['Numequip'])) ?>"<?= $isSelected && !$modeEditionMateriel ? ' aria-current="true"' : '' ?>>Voir</a>
                        <a class="inventory-edit-button" href="<?= e(url('employe/espace.php?onglet=materiel&materiel=' . (int) $equipement['Numequip'] . '&modifier=1')) ?>"<?= $isSelected && $modeEditionMateriel ? ' aria-current="true"' : '' ?>>Modifier</a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
              <p class="inventory-no-results" data-inventory-no-results hidden>Aucun matériel ne correspond à ces filtres.</p>
            </div>
            <footer class="inventory-table-footer">
              <span data-inventory-range></span>
              <nav class="inventory-pagination" aria-label="Pagination de l’inventaire">
                <button type="button" data-inventory-prev aria-label="Page précédente"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                <span data-inventory-pages></span>
                <button type="button" data-inventory-next aria-label="Page suivante"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
              </nav>
            </footer>
          </section>
