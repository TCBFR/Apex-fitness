<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/qr_code.php';
$crud = (new EntityController(QrCode::class))->traiter();
require __DIR__ . '/view/qr_code.php';