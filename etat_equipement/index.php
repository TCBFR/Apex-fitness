<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/etat_equipement.php';
$crud = (new EntityController(EtatEquipement::class))->traiter();
require __DIR__ . '/view/etat_equipement.php';