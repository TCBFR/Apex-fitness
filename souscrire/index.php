<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/souscrire.php';
$crud = (new SouscrireTableController())->traiter();
require __DIR__ . '/view/souscrire.php';