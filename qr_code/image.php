<?php

declare(strict_types=1);

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/model/qr_code.php';

exige_role('ADHERENT');

$qr = (new GenerateurQrCode())->actifPourAdherent(db(), (int) utilisateur()['NumUsers']);
if ($qr === null) {
    http_response_code(404);
    exit('QR code indisponible.');
}

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(503);
    exit('Le générateur QR code n’est pas installé.');
}
require_once $autoload;

$png = (new QRCode(new QROptions([
    'outputType' => QROutputInterface::GDIMAGE_PNG,
    'outputBase64' => false,
    'eccLevel' => EccLevel::M,
    'scale' => 8,
    'quietzoneSize' => 2,
])))->render((string) $qr['code_qr']);

header('Content-Type: image/png');
header('Cache-Control: private, no-store');
header('Content-Length: ' . strlen($png));
echo $png;
