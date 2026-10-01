<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/basic.php';
final class BasicTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new BasicTableModel($pdo)); }
}