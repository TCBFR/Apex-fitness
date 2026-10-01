<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/abonnement.php';
final class AbonnementTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new AbonnementTableModel($pdo)); }
}