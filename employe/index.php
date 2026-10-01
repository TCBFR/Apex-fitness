<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/employe.php';
$crud = (new EmployeTableController())->traiter();
require __DIR__ . '/view/employe.php';