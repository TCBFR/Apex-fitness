<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/faq.php';
final class FaqTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new FaqTableModel($pdo)); }
}