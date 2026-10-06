<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/employe.php';
$crud = (new EntityController(Employe::class))->traiter();
require __DIR__ . '/view/employe.php';