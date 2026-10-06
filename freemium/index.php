<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/FreemiumController.php';
$crud = (new FreemiumController())->traiter();
require __DIR__ . '/view/freemium.php';