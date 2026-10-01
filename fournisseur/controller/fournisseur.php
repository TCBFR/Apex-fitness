<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/fournisseur.php';
final class FournisseurTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new FournisseurTableModel($pdo)); }
}