<?php
require_once __DIR__ . '/../../model/AdherentEspace.php';
$space = new AdherentEspace();
$memberId = (int)utilisateur()['NumUsers'];
$profile = $space->profil($memberId);
$subscription = $space->abonnement($memberId);
$paymentQuery = db()->prepare('SELECT p.date_paiement,p.montant,a.libelle_abo FROM paiement p JOIN abonnement a ON a.Numabo=p.Numabo WHERE p.NumUsers=? ORDER BY p.date_paiement DESC LIMIT 12');
$paymentQuery->execute([$memberId]);
$paymentHistory = array_map(static fn($payment) => [
    'date'=>dateFr($payment['date_paiement']), 'prestation'=>$payment['libelle_abo'],
    'montant'=>number_format((float)$payment['montant'], 2, ',', ' ').' €', 'statut'=>'Payé',
], $paymentQuery->fetchAll());
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
    'ville'=>'', 'adresse_l1'=>'', 'adresse_l2'=>'', 'date_naissance'=>'', 'sexe'=>'',
    'membre_depuis'=>dateFr($profile['inscrit_le'] ?? $profile['cree_le'] ?? null),
    'num_membre'=>$profile['matricule'] ?? '', 'avatar'=>'assets/images/avatar.png',
    'initiale'=>mb_strtoupper(mb_substr($profile['prenom'] ?? '?',0,1)).'.',
];
$abonnement = [
    'formule'=>$subscription['libelle_abo'] ?? 'Aucun abonnement actif',
    'statut'=>$subscription ? 'Actif' : 'Inactif',
    'duree'=>$subscription ? ucfirst($subscription['periodicite']) : '—',
    'date_debut'=>isset($subscription['date_debut']) ? dateFr($subscription['date_debut']) : '—',
    'prochaine_echeance'=>isset($subscription['date_fin']) ? dateFr($subscription['date_fin']) : '—',
    'renouvellement'=>'', 'mois_restants'=>$remainingMonths, 'mois_total'=>$totalMonths,
    'acces'=>[], 'avantages'=>[], 'historique'=>$paymentHistory,
];
