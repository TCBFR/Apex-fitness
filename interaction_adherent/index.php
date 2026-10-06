<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/InteractionAdherentController.php';
$crud = (new InteractionAdherentController())->traiter();
require __DIR__ . '/view/interaction_adherent.php';