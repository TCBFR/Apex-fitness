<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/equipement.php';
$crud = (new EntityController(Equipement::class))->traiter();
require __DIR__ . '/view/equipement.php';