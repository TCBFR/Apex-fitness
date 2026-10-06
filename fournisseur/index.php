<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/fournisseur.php';
$crud = (new EntityController(Fournisseur::class))->traiter();
require __DIR__ . '/view/fournisseur.php';