<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/activite.php';
$crud = (new ActiviteTableController())->traiter();
require __DIR__ . '/view/activite.php';