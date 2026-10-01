<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/freemium.php';
final class FreemiumTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new FreemiumTableModel($pdo)); }
}