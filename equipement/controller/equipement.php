<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/equipement.php';
final class EquipementTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new EquipementTableModel($pdo)); }
}