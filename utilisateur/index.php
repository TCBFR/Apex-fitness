<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/utilisateur.php';
$crud = (new UtilisateurTableController())->traiter();
require __DIR__ . '/view/utilisateur.php';