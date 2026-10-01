<?php
require_once __DIR__ . '/../../Model/CrudTableModel.php';
final class InteractionAdherentTableModel extends CrudTableModel
{
    public function __construct(?PDO $pdo = null) { parent::__construct('interaction_adherent', $pdo); }
}