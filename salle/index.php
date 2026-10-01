<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/salle.php';
$crud = (new SalleTableController())->traiter();
require __DIR__ . '/view/salle.php';