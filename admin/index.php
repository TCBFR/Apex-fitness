<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/admin.php';
$crud = (new AdminTableController())->traiter();
require __DIR__ . '/view/admin.php';