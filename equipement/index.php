<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/equipement.php';
$crud = (new EquipementTableController())->traiter();
require __DIR__ . '/view/equipement.php';