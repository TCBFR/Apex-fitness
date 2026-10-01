<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/abonnement_plus.php';
final class AbonnementPlusTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new AbonnementPlusTableModel($pdo)); }
}