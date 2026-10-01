<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/offre.php';
final class OffreTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new OffreTableModel($pdo)); }
}