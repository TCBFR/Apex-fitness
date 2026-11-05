  <?php if (!$maintenance): ?>
  <?php require __DIR__ . '/equipment-member-tickets.php'; ?>
  <?php endif; ?>

  <?php if ($maintenance): ?>

    <?php require __DIR__ . '/equipment-heading.php'; ?>
      <?php require __DIR__ . '/equipment-order-form.php'; ?>
      <?php require __DIR__ . '/equipment-orders.php'; ?>
      <?php if (!$equipements): ?>
        <div class="inventory-empty">
          <span aria-hidden="true"><i class="fa-solid fa-cube"></i></span>
          <h3>Aucun matériel enregistré</h3>
          <p>L’inventaire ne contient encore aucun équipement.</p>
        </div>
      <?php else: ?>
        <div class="inventory-workspace<?= $materielSelectionne ? '' : ' inventory-workspace-no-detail' ?>">
          <?php require __DIR__ . '/equipment-list.php'; ?>
          <?php if ($materielSelectionne): ?>
          <aside class="inventory-detail-panel" aria-label="<?= $modeEditionMateriel ? 'Modifier le matériel' : 'Détails du matériel' ?>">
            <?php if ($materielSelectionne && $modeEditionMateriel): ?>
              <?php require __DIR__ . '/equipment-detail-edit.php'; ?>
            <?php elseif ($materielSelectionne):
              $statusClass = match ($materielSelectionne['libelle_statut']) {
                'Disponible' => 'available',
                'Maintenance' => 'maintenance',
                default => 'out',
              };
              ?>
              <?php require __DIR__ . '/equipment-detail-view.php'; ?>
            <?php endif; ?>
          </aside>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php else: ?>
    <?php if (!$accueil): ?>
    <h3 style="margin:28px 0 14px">Inventaire du matériel</h3>

    <div class="barre-outils">
      <input type="search" data-filtre="#t-materiel" placeholder="Nom, marque ou n° d'inventaire" aria-label="Rechercher du matériel">
      <select data-filtre="#t-materiel" data-colonne="4" aria-label="Filtrer par état">
        <option value="">Tous les états</option>
        <option value="Disponible">Disponible</option>
        <option value="Maintenance">Maintenance</option>
        <option value="Hors service">Hors service</option>
      </select>
    </div>

    <div class="table-scroll">
      <table class="tableau" id="t-materiel">
        <thead><tr><th>N° inventaire</th><th>Équipement</th><th>Type</th><th>Salle</th><th>État</th><th>Dernière maintenance</th></tr></thead>
        <tbody>
        <?php foreach ($equipements as $eq):
            $cls = $eq['libelle_statut'] === 'Disponible' ? 'tag-ok'
                 : ($eq['libelle_statut'] === 'Maintenance' ? 'tag-warn' : 'tag-bad'); ?>
          <tr>
            <td class="mono"><?= e($eq['num_inventaire']) ?></td>
            <td><?= e($eq['nomequip']) ?><br><span class="mono"><?= e($eq['marque'] . ' ' . $eq['modele']) ?></span></td>
            <td><?= e($eq['libelletypeequip']) ?></td>
            <td><?= e($eq['nomsalle'] ?? '—') ?></td>
            <td><span class="tag <?= $cls ?>"><?= e($eq['libelle_statut']) ?></span></td>
            <td class="mono"><?= dateFr($eq['date_derniere_maintenance']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <p class="vide" data-vide hidden>Aucun équipement ne correspond à cette recherche.</p>
    </div>
    <?php endif; ?>
  <?php endif; ?>
