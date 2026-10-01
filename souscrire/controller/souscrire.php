<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/souscrire.php';
final class SouscrireTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new SouscrireTableModel($pdo)); }
}