<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/AbonnementPlusController.php';
$crud = (new AbonnementPlusController())->traiter();
require __DIR__ . '/view/abonnement_plus.php';