<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/adherent.php';
final class AdherentTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new AdherentTableModel($pdo)); }
}