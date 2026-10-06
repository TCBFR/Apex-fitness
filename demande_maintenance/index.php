<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/../admin/controller/EntityController.php';
require_once __DIR__ . '/model/demande_maintenance.php';
$crud = (new EntityController(DemandeMaintenance::class))->traiter();
require __DIR__ . '/view/demande_maintenance.php';