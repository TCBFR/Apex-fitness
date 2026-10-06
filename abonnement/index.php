<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/../admin/controller/EntityController.php';
require_once __DIR__ . '/model/abonnement.php';
$crud = (new EntityController(Abonnement::class))->traiter();
require __DIR__ . '/view/abonnement.php';