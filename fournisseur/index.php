<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/GestionController.php';
$crud = (new GestionController())->traiterCrud();
require __DIR__ . '/view/fournisseur.php';