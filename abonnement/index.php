<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/abonnement.php';
$crud = (new AbonnementTableController())->traiter();
require __DIR__ . '/view/abonnement.php';