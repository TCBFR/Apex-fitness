<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/FonctionController.php';
$crud = (new FonctionController())->traiter();
require __DIR__ . '/view/fonction.php';