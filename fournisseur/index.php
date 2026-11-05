<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/GestionnaireFournisseur.php';
$crud = (new GestionnaireFournisseur())->traiterCrud();
require __DIR__ . '/view/fournisseur.php';