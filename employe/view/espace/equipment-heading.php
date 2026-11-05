    <section class="maintenance-inventory-page" aria-label="Gestion du matériel">
      <header class="inventory-page-heading">
        <div><h2>Gestion du matériel</h2><p>Suivez les niveaux de stock et les mouvements de l’inventaire.</p></div>
        <a class="inventory-order-link" href="<?= e(url('employe/espace.php?onglet=materiel&commander=1')) ?>"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i> Commander un matériel</a>
      </header>
      <div class="inventory-summary">
        <article class="inventory-summary-card inventory-summary-total">
          <span class="inventory-summary-icon" aria-hidden="true"><i class="fa-solid fa-cube"></i></span>
          <div><strong><?= count($equipements) ?></strong><span>Matériels enregistrés</span><small>Équipements dans l’inventaire</small></div>
        </article>
        <article class="inventory-summary-card inventory-summary-available">
          <span class="inventory-summary-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
          <div><strong><?= $unitesEnStock ?></strong><span>Unités en stock</span><small>Quantité disponible</small></div>
        </article>
        <article class="inventory-summary-card inventory-summary-maintenance">
          <span class="inventory-summary-icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
          <div><strong><?= $nombreMaterielsAlerte ?></strong><span>Stock faible</span><small>Au seuil d’alerte ou en dessous</small></div>
        </article>
        <article class="inventory-summary-card inventory-summary-out">
          <span class="inventory-summary-icon" aria-hidden="true"><i class="fa-solid fa-screwdriver-wrench"></i></span>
          <div><strong><?= $materielEnMaintenance ?></strong><span>En maintenance</span><small><?= $materielDisponible ?> disponible<?= $materielDisponible === 1 ? '' : 's' ?> · <?= $materielHorsService ?> hors service</small></div>
        </article>
      </div>
