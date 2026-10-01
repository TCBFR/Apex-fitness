<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/avis.php';
$crud = (new AvisTableController())->traiter();
require __DIR__ . '/view/avis.php';