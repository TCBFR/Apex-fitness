<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/UtilisateurController.php';
$crud = (new UtilisateurController())->traiterCrud();
require __DIR__ . '/view/utilisateur.php';