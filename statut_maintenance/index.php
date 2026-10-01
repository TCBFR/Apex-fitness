<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/statut_maintenance.php';
$crud = (new StatutMaintenanceTableController())->traiter();
require __DIR__ . '/view/statut_maintenance.php';