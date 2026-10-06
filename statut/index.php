<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/StatutController.php';
$crud = (new StatutController())->traiter();
require __DIR__ . '/view/statut.php';