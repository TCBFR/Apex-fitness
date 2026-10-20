<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/controller/AdherentController.php';
exige_role('ADHERENT');

$paymentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$paymentId || $paymentId < 1) {
    http_response_code(400);
    exit('Identifiant de facture invalide.');
}

(new AdherentController())->telechargerFacture((int)utilisateur()['NumUsers'], $paymentId);
