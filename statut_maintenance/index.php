<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/StatutMaintenanceController.php';
$crud = (new StatutMaintenanceController())->traiter();
require __DIR__ . '/view/statut_maintenance.php';