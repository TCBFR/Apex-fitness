<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/demande_maintenance.php';
final class DemandeMaintenanceTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new DemandeMaintenanceTableModel($pdo)); }
}