<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/../adherent/model/Notification.php';

$notifications = new Notification();
$created = $notifications->preparerRappels();
echo $created . " rappel(s) de séance ajouté(s).\n";
