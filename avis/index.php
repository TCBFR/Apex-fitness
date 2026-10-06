<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/AvisController.php';
$crud = (new AvisController())->traiterCrud();
require __DIR__ . '/view/avis.php';