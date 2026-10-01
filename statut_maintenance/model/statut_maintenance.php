<?php
require_once __DIR__ . '/../../Model/CrudTableModel.php';
final class StatutMaintenanceTableModel extends CrudTableModel
{
    public function __construct(?PDO $pdo = null) { parent::__construct('statut_maintenance', $pdo); }
}