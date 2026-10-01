<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/demande_maintenance.php';
$crud = (new DemandeMaintenanceTableController())->traiter();
require __DIR__ . '/view/demande_maintenance.php';