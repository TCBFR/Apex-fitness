<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/statut.php';
final class StatutTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new StatutTableModel($pdo)); }
}