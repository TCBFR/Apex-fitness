<?php
require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/controller/AccueilController.php';

$activites = (new AccueilController())->getAll();
$titrePage = 'Accueil';
$pageActive = 'accueil';
require __DIR__ . '/view/index.php';