<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/utilisateur.php';
$crud = (new EntityController(Utilisateur::class))->traiter();
require __DIR__ . '/view/utilisateur.php';