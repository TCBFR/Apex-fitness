<?php
/**
 * VUE — réponse JSON du contrôle d'un QR : messages affichés à l'accueil selon le statut.
 * @var string $statut @var array|null $membre @var string|null $formule
 */
$messages = [
    'VALIDE'            => 'Accès autorisé.',
    'FALSIFIE'          => 'QR code non reconnu.',
    'INCONNU'           => 'QR code inconnu.',
    'DESACTIVE'         => 'Ce QR code a été remplacé.',
    'EXPIRE'            => 'QR code expiré.',
    'ABONNEMENT_EXPIRE' => 'Abonnement expiré.',
    'INVALIDE'          => 'Code vide ou trop long.',
];

$payload = [
    'ok'      => $statut === 'VALIDE',
    'statut'  => $statut,
    'message' => $messages[$statut] ?? 'Erreur.',
];
if ($membre !== null) {
    $payload['membre'] = $membre + ($formule !== null ? ['formule' => $formule] : []);
}

return $payload;