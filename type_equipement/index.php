<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/type_equipement.php';
$crud = (new EntityController(TypeEquipement::class))->traiter();
require __DIR__ . '/view/type_equipement.php';