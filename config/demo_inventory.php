<?php

declare(strict_types=1);

function initialiseInventaireFitnessDemo(PDO $pdo): int
{
    $fournisseurQuery = $pdo->prepare(
        'SELECT f.NumUsers FROM fournisseur f
         JOIN utilisateur u ON u.NumUsers = f.NumUsers
         WHERE u.mailUsers = ?'
    );
    $fournisseurQuery->execute(['fournisseur.demo@apex.fr']);
    $fournisseurId = $fournisseurQuery->fetchColumn();
    if ($fournisseurId === false) {
        $accountQuery = $pdo->prepare('SELECT NumUsers FROM utilisateur WHERE mailUsers = ?');
        $accountQuery->execute(['fournisseur.demo@apex.fr']);
        $fournisseurId = $accountQuery->fetchColumn();
        if ($fournisseurId === false) {
            $createAccount = $pdo->prepare(
                'INSERT INTO utilisateur (nomUsers, prenomUsers, mailUsers, telUsers, passwordUsers)
                 VALUES (?, ?, ?, NULL, ?)'
            );
            $createAccount->execute([
                'Apex',
                'Fournitures',
                'fournisseur.demo@apex.fr',
                password_hash('apex2026', PASSWORD_DEFAULT),
            ]);
            $fournisseurId = $pdo->lastInsertId();
        }
        $pdo->prepare(
            'INSERT INTO fournisseur (NumUsers, nomfourn, telephonefourn, siret, mailfourn)
             VALUES (?, ?, NULL, NULL, ?)'
        )->execute([(int) $fournisseurId, 'Fournisseur de démonstration', 'fournisseur.demo@apex.fr']);
    }
    $fournisseurId = (int) $fournisseurId;

    $statutsDemo = [
        ['Disponible', 'EQUIPEMENT', 'etat_equipement'],
        ['Maintenance', 'EQUIPEMENT', 'etat_equipement'],
        ['Hors service', 'EQUIPEMENT', 'etat_equipement'],
        ['Ouverte', 'MAINTENANCE', 'statut_maintenance'],
        ['En attente', 'MAINTENANCE', 'statut_maintenance'],
        ['En cours', 'MAINTENANCE', 'statut_maintenance'],
        ['En attente de piece', 'MAINTENANCE', 'statut_maintenance'],
        ['À valider', 'MAINTENANCE', 'statut_maintenance'],
        ['Terminee', 'MAINTENANCE', 'statut_maintenance'],
    ];
    $findStatut = $pdo->prepare(
        'SELECT Numstatut FROM statut WHERE libelle_statut = ? AND type_statut = ? LIMIT 1'
    );
    $createStatut = $pdo->prepare(
        'INSERT INTO statut (libelle_statut, type_statut) VALUES (?, ?)'
    );
    $createEtatEquipement = $pdo->prepare(
        'INSERT IGNORE INTO etat_equipement (Numstatut) VALUES (?)'
    );
    $createStatutMaintenance = $pdo->prepare(
        'INSERT IGNORE INTO statut_maintenance (Numstatut) VALUES (?)'
    );
    foreach ($statutsDemo as [$libelle, $type, $table]) {
        $findStatut->execute([$libelle, $type]);
        $statutId = $findStatut->fetchColumn();
        if ($statutId === false) {
            $createStatut->execute([$libelle, $type]);
            $statutId = $pdo->lastInsertId();
        }
        if ($table === 'etat_equipement') {
            $createEtatEquipement->execute([(int) $statutId]);
        } else {
            $createStatutMaintenance->execute([(int) $statutId]);
        }
    }

    $typesDemo = [
        ['Cardio-training', 'Équipements de cardio-training'],
        ['Musculation', 'Appareils et accessoires de musculation'],
        ['Accessoire fitness', 'Accessoires pour les activités fitness'],
    ];
    $findType = $pdo->prepare(
        'SELECT Numtypeequip FROM type_equipement WHERE libelletypeequip = ? LIMIT 1'
    );
    $createType = $pdo->prepare(
        'INSERT INTO type_equipement (libelletypeequip, descriptionequip) VALUES (?, ?)'
    );
    foreach ($typesDemo as [$libelle, $description]) {
        $findType->execute([$libelle]);
        if ($findType->fetchColumn() === false) {
            $createType->execute([$libelle, $description]);
        }
    }

    $findDisponible = $pdo->prepare(
        "SELECT Numstatut FROM statut
         WHERE libelle_statut = 'Disponible' AND type_statut = 'EQUIPEMENT' LIMIT 1"
    );
    $findDisponible->execute();
    $statutDisponible = (int) $findDisponible->fetchColumn();

    $equipementsDemo = [
        ['Tapis de course', 'RUN-500', 1899.00, 'Cardio-training'],
        ['Vélo elliptique', 'ELLIPTIC-300', 1299.00, 'Cardio-training'],
        ['Vélo d’appartement', 'BIKE-200', 799.00, 'Cardio-training'],
        ['Rameur', 'ROW-400', 999.00, 'Cardio-training'],
        ['Presse à jambes', 'LEG-PRESS-01', 2499.00, 'Musculation'],
        ['Banc de musculation', 'BENCH-02', 499.00, 'Musculation'],
        ['Haltères (lot)', 'DUMBBELL-SET', 350.00, 'Musculation'],
        ['Tapis de fitness', 'FIT-MAT-01', 29.00, 'Accessoire fitness'],
    ];
    $findEquipement = $pdo->prepare(
        'SELECT Numequip FROM equipement WHERE nomequip = ? AND modele = ? LIMIT 1'
    );
    $findTypeId = $pdo->prepare(
        'SELECT Numtypeequip FROM type_equipement WHERE libelletypeequip = ? LIMIT 1'
    );
    $createEquipement = $pdo->prepare(
        'INSERT INTO equipement
         (nomequip, marque, modele, date_achatequip, prix_achatequip, Numtypeequip, NumUsers_fourn, Numstatut)
         VALUES (?, ?, ?, CURRENT_DATE, ?, ?, ?, ?)'
    );
    $ajoutes = 0;
    foreach ($equipementsDemo as [$nom, $modele, $prix, $type]) {
        $findEquipement->execute([$nom, $modele]);
        if ($findEquipement->fetchColumn() !== false) {
            continue;
        }
        $findTypeId->execute([$type]);
        $typeId = $findTypeId->fetchColumn();
        if ($typeId === false || $statutDisponible === 0) {
            throw new RuntimeException('Les types ou statuts nécessaires à l’inventaire de démonstration sont absents.');
        }
        $createEquipement->execute([
            $nom,
            'Apex Fitness',
            $modele,
            $prix,
            (int) $typeId,
            $fournisseurId,
            $statutDisponible,
        ]);
        $ajoutes++;
    }

    return $ajoutes;
}
