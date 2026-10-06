<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/salle.php';
$crud = (new EntityController(Salle::class))->traiter();
require __DIR__ . '/view/salle.php';