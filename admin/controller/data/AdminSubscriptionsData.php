<?php

$abonnementsAdmin = [];
$formulesAdmin = [];
$abonnementSelectionne = null;
$paiementsAbonnement = [];
$vueAbonnements = $_GET['vue'] ?? 'detail';
if (!is_string($vueAbonnements) || !in_array($vueAbonnements, ['detail', 'liste'], true)) {
    $vueAbonnements = 'detail';
}
if ($onglet === 'abonnements') {
    $abonnementsAdmin = $pdo->query(
        "SELECT s.NumUsers, s.Numabo, s.date_debut, s.date_fin, s.statut_souscription,
                u.nomUsers, u.prenomUsers, u.mailUsers, ab.libelle_abo, o.type_offre,
                CASE o.type_offre WHEN 'FREEMIUM' THEN f.prix_mensuel_reduit
                     WHEN 'BASIC' THEN b.prix_annuel WHEN 'PLUS' THEN p.prix_mensuel_plus ELSE 0 END AS tarif,
                (SELECT pa.montant FROM paiement pa WHERE pa.NumUsers=s.NumUsers AND pa.Numabo=s.Numabo ORDER BY pa.date_paiement DESC, pa.Numpaiement DESC LIMIT 1) AS dernier_montant,
                (SELECT pa.date_paiement FROM paiement pa WHERE pa.NumUsers=s.NumUsers AND pa.Numabo=s.Numabo ORDER BY pa.date_paiement DESC, pa.Numpaiement DESC LIMIT 1) AS dernier_paiement,
                (SELECT pa.moyen_paiement FROM paiement pa WHERE pa.NumUsers=s.NumUsers AND pa.Numabo=s.Numabo ORDER BY pa.date_paiement DESC, pa.Numpaiement DESC LIMIT 1) AS moyen_paiement
           FROM souscrire s
           JOIN utilisateur u ON u.NumUsers=s.NumUsers
           JOIN abonnement ab ON ab.Numabo=s.Numabo
           JOIN offre o ON o.Numoffre=ab.Numoffre
           LEFT JOIN freemium f ON f.Numoffre=o.Numoffre
           LEFT JOIN basic b ON b.Numoffre=o.Numoffre
           LEFT JOIN abonnement_plus p ON p.Numoffre=o.Numoffre
          ORDER BY s.date_debut DESC, u.nomUsers, u.prenomUsers"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($abonnementsAdmin as &$abonnementRow) {
        if ($abonnementRow['statut_souscription'] === 'Suspendue') $abonnementRow['statut_affiche'] = 'Suspendu';
        elseif ($abonnementRow['statut_souscription'] === 'Resiliee') $abonnementRow['statut_affiche'] = 'Résilié';
        elseif ($abonnementRow['statut_souscription'] === 'Terminee' || ($abonnementRow['date_fin'] && $abonnementRow['date_fin'] < date('Y-m-d'))) $abonnementRow['statut_affiche'] = 'Expiré';
        elseif ($abonnementRow['date_fin'] && (strtotime($abonnementRow['date_fin']) - strtotime('today')) <= 30 * 86400) $abonnementRow['statut_affiche'] = 'À renouveler';
        else $abonnementRow['statut_affiche'] = 'Actif';
    }
    unset($abonnementRow);
    $formulesAdmin = $pdo->query('SELECT Numabo, libelle_abo FROM abonnement WHERE actif_abo=1 ORDER BY libelle_abo')->fetchAll(PDO::FETCH_ASSOC);
    if ($vueAbonnements !== 'liste') {
        $selectionId = (int) ($_GET['id'] ?? 0);
        $selectionFormule = (int) ($_GET['formule'] ?? 0);
        $selectionDate = (string) ($_GET['debut'] ?? '');
        foreach ($abonnementsAdmin as $aboRow) {
            if ((int) $aboRow['NumUsers'] === $selectionId
                && (!$selectionFormule || (int) $aboRow['Numabo'] === $selectionFormule)
                && ($selectionDate === '' || $aboRow['date_debut'] === $selectionDate)) {
                $abonnementSelectionne = $aboRow;
                break;
            }
        }
        if (!$abonnementSelectionne && !$selectionId && $abonnementsAdmin) {
            $abonnementSelectionne = $abonnementsAdmin[0];
        }
    }
    if ($abonnementSelectionne) {
        $queryPaiementsAbo = $pdo->prepare('SELECT Numpaiement, date_paiement, montant, moyen_paiement FROM paiement WHERE NumUsers=? AND Numabo=? ORDER BY date_paiement DESC, Numpaiement DESC');
        $queryPaiementsAbo->execute([$abonnementSelectionne['NumUsers'], $abonnementSelectionne['Numabo']]);
        $paiementsAbonnement = $queryPaiementsAbo->fetchAll(PDO::FETCH_ASSOC);
    }
}
