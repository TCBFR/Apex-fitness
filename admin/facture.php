<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');

$paymentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$paymentId || $paymentId < 1) {
    http_response_code(400);
    exit('Identifiant de facture invalide.');
}

$query = db()->prepare('SELECT NumUsers FROM paiement WHERE Numpaiement = ?');
$query->execute([$paymentId]);
$memberId = $query->fetchColumn();
if (!$memberId) {
    http_response_code(404);
    exit('Facture introuvable.');
}

require_once __DIR__ . '/../adherent/controller/AdherentController.php';
(new AdherentController())->telechargerFacture((int) $memberId, (int) $paymentId);
