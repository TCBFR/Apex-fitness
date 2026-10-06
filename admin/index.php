<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/EntityController.php';
require_once __DIR__ . '/model/admin.php';
$crud = (new EntityController(Admin::class))->traiter();
require __DIR__ . '/view/admin.php';