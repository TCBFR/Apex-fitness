<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/SalleController.php';
$crud = (new SalleController())->traiter();
require __DIR__ . '/view/salle.php';