<?php
require_once __DIR__ . '/../../model/AdherentEspace.php';
require_once dirname(__DIR__, 2) . '/controller/AdherentController.php';
require_once dirname(__DIR__, 3) . '/qr_code/model/qr_code.php';
$space = new AdherentEspace();
$memberId = (int)utilisateur()['NumUsers'];
$profile = $space->profil($memberId);
(new GenerateurQrCode())->assurerPourAdherent(
    db(),
    $memberId,
    (string) ($profile['matricule'] ?? '')
);
$subscription = $space->abonnement($memberId);
$paymentHistory = array_map(static fn($payment) => [
    'id'=>(int)$payment['Numpaiement'], 'raw_date'=>$payment['date_paiement'],
    'date'=>dateFr($payment['date_paiement']), 'prestation'=>$payment['libelle_abo'],
    'montant'=>number_format((float)$payment['montant'], 2, ',', ' ').' €', 'statut'=>'Payé',
], (new AdherentController($space))->paiements($memberId));
$totalMonths = 0;
$remainingMonths = 0;
if ($subscription && $subscription['date_fin']) {
    $startDate = new DateTimeImmutable($subscription['date_debut']);
    $endDate = new DateTimeImmutable($subscription['date_fin']);
    $today = new DateTimeImmutable('today');
    $totalMonths = max(1, (int)ceil(($endDate->getTimestamp() - $startDate->getTimestamp()) / (30 * 86400)));
    $remainingMonths = max(0, (int)ceil(($endDate->getTimestamp() - $today->getTimestamp()) / (30 * 86400)));
}
$user = [
    'prenom'=>$profile['prenom'] ?? '', 'nom'=>$profile['nom'] ?? '',
    'email'=>$profile['email'] ?? '', 'telephone'=>$profile['telephone'] ?? '',
    'id_commune'=>$profile['id_commune'] ?? '',
    'nom_commune'=>$profile['nom_commune'] ?? '',
    'code_postal'=>$profile['code_postal'] ?? '',
    'adresse_postale'=>$profile['adresse_postale'] ?? '',
    'ville'=>$profile['nom_commune'] ?? '', 'adresse_l1'=>'', 'adresse_l2'=>'',
    'date_naissance'=>$profile['date_naissance'] ?? '', 'sexe'=>$profile['sexe'] ?? '',
    'membre_depuis'=>dateFr($profile['inscrit_le'] ?? $profile['cree_le'] ?? null),
    'num_membre'=>$profile['matricule'] ?? '', 'avatar'=>$profile['photo_profil'] ?? '',
    'qr_image'=>url('qr_code/image.php'),
    'initiale'=>mb_strtoupper(mb_substr($profile['prenom'] ?? '?',0,1)).'.',
];
$abonnement = [
    'formule'=>$subscription['libelle_abo'] ?? 'Aucun abonnement actif',
    'statut'=>$subscription ? 'Actif' : 'Inactif',
    'duree'=>$subscription ? ucfirst($subscription['periodicite']) : '—',
    'date_debut'=>isset($subscription['date_debut']) ? dateFr($subscription['date_debut']) : '—',
    'prochaine_echeance'=>isset($subscription['date_fin']) ? dateFr($subscription['date_fin']) : '—',
    'renouvellement'=>'', 'mois_restants'=>$remainingMonths, 'mois_total'=>$totalMonths,
    'acces'=>[], 'historique'=>$paymentHistory,
];
