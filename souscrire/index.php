<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/SouscrireController.php';
$crud = (new SouscrireController())->traiter();
require __DIR__ . '/view/souscrire.php';