<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/QrCodeController.php';
$crud = (new QrCodeController())->traiter();
require __DIR__ . '/view/qr_code.php';