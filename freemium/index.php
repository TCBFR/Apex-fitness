<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/freemium.php';
$crud = (new FreemiumTableController())->traiter();
require __DIR__ . '/view/freemium.php';