<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/activite.php';
final class ActiviteTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new ActiviteTableModel($pdo)); }
}