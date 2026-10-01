<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/employe.php';
final class EmployeTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new EmployeTableModel($pdo)); }
}