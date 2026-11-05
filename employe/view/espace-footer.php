
  </div>
</div>
<?php if ($onglet === 'creneaux'):
  foreach (PlanningController::FC_SCRIPTS as $src): ?>
    <script src="<?= e($src) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>
<?php if ($maintenance && $onglet === 'materiel' && $equipements): ?>
<script src="<?= e(url('assets/js/employe/inventory-filter.js')) ?>"></script>
<?php endif; ?>
<?php if (($maintenance && $onglet === 'maintenance' && $maintenances) || ($accueil && $onglet === 'suivi-tickets' && $ticketsEmploye)): ?>
<script src="<?= e(url('assets/js/employe/ticket-filter.js')) ?>"></script>
<?php endif; ?>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
