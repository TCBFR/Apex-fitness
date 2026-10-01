<?php
require_once __DIR__ . '/../../Model/CrudTableModel.php';
final class DemandeMaintenanceTableModel extends CrudTableModel
{
    public function __construct(?PDO $pdo = null) { parent::__construct('demande_maintenance', $pdo); }
}