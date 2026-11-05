    </main>
  </div>
</div>
<?php if ($onglet === 'planning'): foreach (PlanningController::FC_SCRIPTS as $src): ?>
  <script src="<?= e($src) ?>"></script>
<?php endforeach; endif; ?>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
<?php if (in_array($onglet, ['coachs', 'utilisateurs'], true)): ?>
  <script src="<?= e(url('assets/js/commune/commune.js')) ?>"></script>
<?php endif; ?>
</body>
</html>