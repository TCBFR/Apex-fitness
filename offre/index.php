<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/OffreController.php';
$crud = (new OffreController())->traiter();
require __DIR__ . '/view/offre.php';