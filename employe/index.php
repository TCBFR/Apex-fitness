<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/EmployeController.php';
$crud = (new EmployeController())->traiter();
require __DIR__ . '/view/employe.php';