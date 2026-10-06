<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/../admin/controller/EntityController.php';
require_once __DIR__ . '/model/souscrire.php';
$crud = (new EntityController(Souscrire::class))->traiter();
require __DIR__ . '/view/souscrire.php';