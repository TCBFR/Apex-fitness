<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/type_equipement.php';
final class TypeEquipementTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new TypeEquipementTableModel($pdo)); }
}