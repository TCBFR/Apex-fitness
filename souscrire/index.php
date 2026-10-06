<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/souscrire.php';
$crud = (new EntityController(Souscrire::class))->traiter();
require __DIR__ . '/view/souscrire.php';