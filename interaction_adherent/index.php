<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/interaction_adherent.php';
$crud = (new InteractionAdherentTableController())->traiter();
require __DIR__ . '/view/interaction_adherent.php';