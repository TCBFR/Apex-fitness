<?php
require_once __DIR__ . '/../../Model/CrudTableModel.php';
final class TypeEquipementTableModel extends CrudTableModel
{
    public function __construct(?PDO $pdo = null) { parent::__construct('type_equipement', $pdo); }
}