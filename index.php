<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controller/AccueilController.php';

$activites = (new AccueilController())->getAll();
$titrePage = 'Accueil';
$pageActive = 'accueil';
require __DIR__ . '/views/index.php';