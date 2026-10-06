<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/EtatEquipementController.php';
$crud = (new EtatEquipementController())->traiter();
require __DIR__ . '/view/etat_equipement.php';