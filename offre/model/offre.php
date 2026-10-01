<?php
require_once __DIR__ . '/../../Model/CrudTableModel.php';
final class OffreTableModel extends CrudTableModel
{
    public function __construct(?PDO $pdo = null) { parent::__construct('offre', $pdo); }
}