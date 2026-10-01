<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/statut.php';
$crud = (new StatutTableController())->traiter();
require __DIR__ . '/view/statut.php';