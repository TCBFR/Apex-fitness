<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/etat_equipement.php';
$crud = (new EtatEquipementTableController())->traiter();
require __DIR__ . '/view/etat_equipement.php';