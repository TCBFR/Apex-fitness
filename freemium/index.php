<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/../admin/controller/EntityController.php';
require_once __DIR__ . '/model/freemium.php';
$crud = (new EntityController(Freemium::class))->traiter();
require __DIR__ . '/view/freemium.php';