<?php
require_once dirname(__DIR__) . '/includes/planning/layout.php';
page_head('planning', 'Planning');
hero('calweek', 'Planning', 'Découvrez et réservez vos séances en quelques clics.<br>Cours collectifs, musculation, cardio et bien plus encore.', 'planning-hero');
?>
<?php include __DIR__ . '/planning-calendar.php'; ?>
<?php include __DIR__ . '/planning-modal.php'; ?>
<script src="<?= e(url('assets/js/adherent/planning.js')) ?>"></script>
<?php page_foot(); ?>
