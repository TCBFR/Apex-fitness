<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/fonction.php';
$crud = (new FonctionTableController())->traiter();
require __DIR__ . '/view/fonction.php';