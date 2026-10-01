<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/utilisateur.php';
final class UtilisateurTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new UtilisateurTableModel($pdo)); }
}