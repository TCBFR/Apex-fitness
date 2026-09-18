<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controller/AbonnementController.php';
$controller = new AbonnementController();
$offres = $controller->getAll();
$aboCourant = a_role('ADHERENT') ? $controller->get((int) utilisateur()['NumUsers']) : null;
$titrePage = 'Abonnements';
$pageActive = 'abonnements';
require __DIR__ . '/views/abonnements.php';
