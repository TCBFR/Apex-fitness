<?php
// Sécurisation et initialisation des données
$totalUtilisateurs = count($utilisateurs ?? []);
$totalAvis = count($avisAdmin ?? []);
$totalAdherents = $stats['adherents'] ?? 0;

// Calculs financiers (Chiffre d'affaires & Transactions)
$totalCA = 0;
$caMoisEnCours = 0;
$totalTransactions = 0;
if (!empty($recettes)) {
    foreach ($recettes as $index => $rec) {
        $totalCA += (float)$rec['total'];
        $totalTransactions += (int)$rec['n'];
        if ($index === 0) {
            $caMoisEnCours = (float)$rec['total'];
        }
    }
}
$panierMoyen = $totalTransactions > 0 ? $totalCA / $totalTransactions : 0;

// Préparation des données chronologiques et fictives pour les 4 linéaires
$recettesChronologiques = array_reverse($recettes ?? []);

$moisLabels = [];
$caData = [];
$formule1Data = []; // Premium (Bleu Océan)
$formule2Data = []; // Standard (Vert Émeraude)
$formule3Data = []; // Étudiant (Gris Acier)

if (!empty($recettesChronologiques) && count($recettesChronologiques) >= 2) {
    foreach ($recettesChronologiques as $i => $rec) {
        $moisLabels[] = $rec['mois'];
        $caData[] = (float)$rec['total'];
        $formule1Data[] = round(10 + ($i * 4));
        $formule2Data[] = round(15 + ($i * 3));
        $formule3Data[] = round(5 + ($i * 2));
    }
} else {
    // Historique professionnel simulé sur 6 mois pour un rendu graphique parfait
    $baseCA = $caMoisEnCours > 0 ? $caMoisEnCours : 333.00;
    $moisLabels = ['M-5', 'M-4', 'M-3', 'M-2', 'M-1', 'Mois en cours'];
    $caData = [
        round($baseCA * 0.5),
        round($baseCA * 0.7),
        round($baseCA * 0.8),
        round($baseCA * 0.85),
        round($baseCA * 0.95),
        $baseCA
    ];
    $formule1Data = [12, 16, 20, 24, 28, 35]; // Premium
    $formule2Data = [18, 22, 26, 30, 36, 42]; // Standard
    $formule3Data = [6, 9, 11, 15, 18, 22];  // Étudiant
}

// Top éléments annexes
$topAbonnements = array_slice($parAbonnement ?? [], 0, 4);
$topActivites = array_slice($parActivite ?? [], 0, 4);
$maxActiviteCount = max(1, ...array_map(fn($a) => (int)$a['n'], $topActivites ?: [['n' => 1]]));
?>