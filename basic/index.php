<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/basic.php';
$crud = (new BasicTableController())->traiter();
require __DIR__ . '/view/basic.php';