<?php
require_once __DIR__ . '/../../Model/CrudTableModel.php';
final class FonctionTableModel extends CrudTableModel
{
    public function __construct(?PDO $pdo = null) { parent::__construct('fonction', $pdo); }
}