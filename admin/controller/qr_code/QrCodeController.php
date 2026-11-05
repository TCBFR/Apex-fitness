<?php
/**
 * QrCode Controller - Routes for QR code functions
 * Organized MVC structure for Apex Fitness application
 */

// Core qr_code routes
$routes = [];

$routes['qr_code/index.php'] = '../qr_code/index.php';
$routes['qr_code/image.php'] = '../qr_code/image.php';
$routes['qr_code/verifier.php'] = '../qr_code/verifier.php';
$routes['qr_code/verifier-api.php'] = '../qr_code/verifier-api.php';

return $routes;