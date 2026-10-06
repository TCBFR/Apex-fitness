<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/offre.php';
$crud = (new EntityController(Offre::class))->traiter();
require __DIR__ . '/view/offre.php';