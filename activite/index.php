<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/ActiviteController.php';
$crud = (new ActiviteController())->traiter();
require __DIR__ . '/view/activite.php';