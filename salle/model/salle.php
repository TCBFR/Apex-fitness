<?php
require_once __DIR__ . '/../../Model/CrudTableModel.php';
final class SalleTableModel extends CrudTableModel
{
    public function __construct(?PDO $pdo = null) { parent::__construct('salle', $pdo); }
}