<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/AbonnementController.php';
$crud = (new AbonnementController())->traiterCrud();
require __DIR__ . '/view/abonnement.php';