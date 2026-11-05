<?php

$historiquePaiements = [];
$paiementSelectionneAdmin = null;
$vuePaiements = $_GET['vue'] ?? 'detail';
if ($onglet === 'paiements') {
    $historiquePaiements = $pdo->query(
        'SELECT p.Numpaiement, p.date_paiement, p.montant, p.moyen_paiement,
                u.NumUsers, u.prenomUsers, u.nomUsers, u.mailUsers,
                ab.Numabo, ab.libelle_abo,
                (SELECT s.date_debut FROM souscrire s WHERE s.NumUsers=p.NumUsers AND s.Numabo=p.Numabo ORDER BY s.date_debut DESC LIMIT 1) AS date_debut_abo,
                (SELECT s.date_fin FROM souscrire s WHERE s.NumUsers=p.NumUsers AND s.Numabo=p.Numabo ORDER BY s.date_debut DESC LIMIT 1) AS date_fin_abo
           FROM paiement p
           JOIN adherent d ON d.NumUsers = p.NumUsers
           JOIN utilisateur u ON u.NumUsers = d.NumUsers
           JOIN abonnement ab ON ab.Numabo = p.Numabo
          ORDER BY p.date_paiement DESC, p.Numpaiement DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
    $idPaiementSelectionne = (int) ($_GET['paiement'] ?? 0);
    foreach ($historiquePaiements as $paiementAdmin) {
        if ((int) $paiementAdmin['Numpaiement'] === $idPaiementSelectionne) { $paiementSelectionneAdmin = $paiementAdmin; break; }
    }
    if (!$paiementSelectionneAdmin && !$idPaiementSelectionne && $vuePaiements !== 'liste' && $historiquePaiements) {
        $paiementSelectionneAdmin = $historiquePaiements[0];
    }
}
