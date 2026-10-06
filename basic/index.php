<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/../admin/controller/EntityController.php';
require_once __DIR__ . '/model/basic.php';
$crud = (new EntityController(Basic::class))->traiter();
require __DIR__ . '/view/basic.php';