<?php
/**
 * VUE — rendu PNG d'un QR code. Reçoit $valeur, écrit l'image binaire (les en-têtes sont posés par qr_image()).
 * @var string $valeur
 */

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode as ChillerlanQRCode;
use chillerlan\QRCode\QROptions;

// Pas d'autoload global : on charge celui de Composer uniquement ici, si présent.
if (!class_exists(ChillerlanQRCode::class, false)) {
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }
}
if (!class_exists(ChillerlanQRCode::class)) {
    throw new RuntimeException('chillerlan/php-qrcode introuvable : lancez `composer require chillerlan/php-qrcode`.');
}

echo (new ChillerlanQRCode(new QROptions([
    'outputType'    => QROutputInterface::GDIMAGE_PNG,
    'outputBase64'  => false,
    'eccLevel'      => EccLevel::M,
    'scale'         => 8,
    'quietzoneSize' => 2,
])))->render($valeur);
