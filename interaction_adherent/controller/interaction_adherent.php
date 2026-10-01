<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/interaction_adherent.php';
final class InteractionAdherentTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new InteractionAdherentTableModel($pdo)); }
}