<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/PaiementController.php';
$crud = (new PaiementController())->traiterCrud();
require __DIR__ . '/view/paiement.php';