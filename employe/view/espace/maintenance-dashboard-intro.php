  <section class="maintenance-dashboard" aria-labelledby="maintenance-welcome-title">
    <header class="maintenance-welcome">
      <div>
        <p class="maintenance-eyebrow">ESPACE MAINTENANCE</p>
        <h2 id="maintenance-welcome-title">Bonjour <?= e($prenomTechnicien !== '' ? $prenomTechnicien : 'Technicien') ?>,</h2>
        <p>Voici un aperçu de votre activité maintenance.</p>
      </div>
    </header>

    <div class="maintenance-metrics">
      <a class="maintenance-metric metric-open" href="<?= e(url('employe/espace.php?onglet=maintenance')) ?>">
        <span class="maintenance-metric-icon" aria-hidden="true">▤</span>
        <span class="maintenance-metric-copy"><strong><?= $ticketsOuvertsMaintenance ?></strong><span>Tickets ouverts</span><small>Voir mes tickets →</small></span>
      </a>
      <a class="maintenance-metric metric-done" href="<?= e(url('employe/espace.php?onglet=maintenance')) ?>">
        <span class="maintenance-metric-icon" aria-hidden="true">✓</span>
        <span class="maintenance-metric-copy"><strong><?= $ticketsResolus30Jours ?></strong><span>Tickets résolus (30 j)</span><small>Voir l’historique →</small></span>
      </a>
      <a class="maintenance-metric metric-waiting" href="<?= e(url('employe/espace.php?onglet=maintenance')) ?>">
        <span class="maintenance-metric-icon" aria-hidden="true">◷</span>
        <span class="maintenance-metric-copy"><strong><?= $ticketsEnAttenteMaintenance ?></strong><span>En attente</span><small>Voir les détails →</small></span>
      </a>
      <a class="maintenance-metric metric-equipment" href="<?= e(url('employe/espace.php?onglet=materiel')) ?>">
        <span class="maintenance-metric-icon" aria-hidden="true">⬡</span>
        <span class="maintenance-metric-copy"><strong><?= count($equipementsMaintenanceAControler) ?></strong><span>Équipements à contrôler</span><small>Voir le matériel →</small></span>
      </a>
    </div>
