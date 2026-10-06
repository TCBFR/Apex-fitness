<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/activite.php';
$crud = (new EntityController(Activite::class))->traiter();
require __DIR__ . '/view/activite.php';