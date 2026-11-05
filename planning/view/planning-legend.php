<ul class="planning-legende" aria-label="Légende des activités">
  <?php foreach (PlanningController::TYPES as $cle => $lib): ?>
    <li class="act-<?= e($cle) ?>"><?= e($lib) ?></li>
  <?php endforeach; ?>
  <li class="act-complet">Complet</li>
</ul>
