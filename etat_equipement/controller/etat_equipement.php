<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/etat_equipement.php';
final class EtatEquipementTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new EtatEquipementTableModel($pdo)); }
}