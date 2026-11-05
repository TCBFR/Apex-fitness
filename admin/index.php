<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/ControleurGenerique.php';
require_once __DIR__ . '/model/admin.php';
$crud = (new ControleurGenerique(Admin::class))->traiter();
require __DIR__ . '/view/admin.php';