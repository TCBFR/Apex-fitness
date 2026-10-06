<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/../admin/controller/EntityController.php';
require_once __DIR__ . '/model/statut_maintenance.php';
$crud = (new EntityController(StatutMaintenance::class))->traiter();
require __DIR__ . '/view/statut_maintenance.php';