<?php
require_once __DIR__ . '/../../Model/CrudTableModel.php';
final class AbonnementPlusTableModel extends CrudTableModel
{
    public function __construct(?PDO $pdo = null) { parent::__construct('abonnement_plus', $pdo); }
}