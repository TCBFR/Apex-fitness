<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/BasicController.php';
$crud = (new BasicController())->traiter();
require __DIR__ . '/view/basic.php';