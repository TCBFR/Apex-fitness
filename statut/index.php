<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/statut.php';
$crud = (new EntityController(Statut::class))->traiter();
require __DIR__ . '/view/statut.php';