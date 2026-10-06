<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/DemandeMaintenanceController.php';
$crud = (new DemandeMaintenanceController())->traiter();
require __DIR__ . '/view/demande_maintenance.php';