<?php
require_once __DIR__ . '/../../Model/CrudTableModel.php';
final class EmployeTableModel extends CrudTableModel
{
    public function __construct(?PDO $pdo = null) { parent::__construct('employe', $pdo); }
}