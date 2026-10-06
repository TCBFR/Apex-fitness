<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/freemium.php';
$crud = (new EntityController(Freemium::class))->traiter();
require __DIR__ . '/view/freemium.php';