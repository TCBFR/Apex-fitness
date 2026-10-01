<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/admin.php';
final class AdminTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new AdminTableModel($pdo)); }
}