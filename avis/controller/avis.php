<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/avis.php';
final class AvisTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new AvisTableModel($pdo)); }
}