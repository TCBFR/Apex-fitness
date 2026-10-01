<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/qr_code.php';
$crud = (new QrCodeTableController())->traiter();
require __DIR__ . '/view/qr_code.php';