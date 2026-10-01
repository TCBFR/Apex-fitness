<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/statut_maintenance.php';
final class StatutMaintenanceTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new StatutMaintenanceTableModel($pdo)); }
}