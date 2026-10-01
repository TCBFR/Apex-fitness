<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/type_equipement.php';
$crud = (new TypeEquipementTableController())->traiter();
require __DIR__ . '/view/type_equipement.php';