<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/qr_bootstrap.php';

exige_role('EMPLOYE');
profil_dispatch('QrCodeController', 'index');