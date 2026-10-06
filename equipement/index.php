<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/EquipementController.php';
$crud = (new EquipementController())->traiter();
require __DIR__ . '/view/equipement.php';