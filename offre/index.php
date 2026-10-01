<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/offre.php';
$crud = (new OffreTableController())->traiter();
require __DIR__ . '/view/offre.php';