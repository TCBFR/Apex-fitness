<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/fournisseur.php';
$crud = (new FournisseurTableController())->traiter();
require __DIR__ . '/view/fournisseur.php';