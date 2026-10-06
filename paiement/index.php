<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/../admin/controller/EntityController.php';
require_once __DIR__ . '/model/paiement.php';
$crud = (new EntityController(Paiement::class))->traiter();
require __DIR__ . '/view/paiement.php';