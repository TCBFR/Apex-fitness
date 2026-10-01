<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/abonnement_plus.php';
$crud = (new AbonnementPlusTableController())->traiter();
require __DIR__ . '/view/abonnement_plus.php';