<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/paiement.php';
final class PaiementTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new PaiementTableModel($pdo)); }
}