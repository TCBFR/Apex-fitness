<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/paiement.php';
$crud = (new PaiementTableController())->traiter();
require __DIR__ . '/view/paiement.php';