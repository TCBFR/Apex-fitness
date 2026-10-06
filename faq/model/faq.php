<?php
require_once __DIR__ . '/../../Model/CrudTableModel.php';
final class FaqTableModel extends CrudTableModel
{
    public function __construct(?PDO $pdo = null) { parent::__construct('faq', $pdo); }
}
//final class FaqTableModel signifie que cette 
//classe ne peut pas être étendue par une autre classe