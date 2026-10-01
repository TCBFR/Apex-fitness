<?php
require_once __DIR__ . '/../../Model/CrudTableModel.php';
final class EtatEquipementTableModel extends CrudTableModel
{
    public function __construct(?PDO $pdo = null) { parent::__construct('etat_equipement', $pdo); }
}