<?php
require_once __DIR__ . '/../../controller/crud/TableCrudController.php';
require_once __DIR__ . '/../model/reservation.php';
final class ReservationTableController extends TableCrudController
{
    public function __construct(?PDO $pdo = null) { parent::__construct(new ReservationTableModel($pdo)); }
}