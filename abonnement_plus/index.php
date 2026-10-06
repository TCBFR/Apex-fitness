<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/abonnement_plus.php';
$crud = (new EntityController(AbonnementPlus::class))->traiter();
require __DIR__ . '/view/abonnement_plus.php';