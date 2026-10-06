<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/TypeEquipementController.php';
$crud = (new TypeEquipementController())->traiter();
require __DIR__ . '/view/type_equipement.php';