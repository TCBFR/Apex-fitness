<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/fonction.php';
$crud = (new EntityController(Fonction::class))->traiter();
require __DIR__ . '/view/fonction.php';