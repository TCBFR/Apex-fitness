<?php

declare(strict_types=1);

final class InventaireDemo
{
    public function __construct(private PDO $pdo)
    {
    }

    public function initialiser(): int
    {
        $fournisseurId = $this->trouverOuCreerFournisseur();

        $this->creerStatutsNecessaires();
        $this->creerTypesNecessaires();

        return $this->ajouterEquipementsManquants($fournisseurId);
    }

    private function trouverOuCreerFournisseur(): int
    {
        $email = 'fournisseur.demo@apex.fr';
        $requete = $this->pdo->prepare(
            'SELECT f.NumUsers FROM fournisseur f
             JOIN utilisateur u ON u.NumUsers = f.NumUsers
             WHERE u.mailUsers = ?'
        );
        $requete->execute([$email]);
        $fournisseurId = $requete->fetchColumn();

        if ($fournisseurId !== false) {
            return (int) $fournisseurId;
        }

        $requete = $this->pdo->prepare('SELECT NumUsers FROM utilisateur WHERE mailUsers = ?');
        $requete->execute([$email]);
        $fournisseurId = $requete->fetchColumn();

        if ($fournisseurId === false) {
            $requete = $this->pdo->prepare(
                'INSERT INTO utilisateur (nomUsers, prenomUsers, mailUsers, telUsers, passwordUsers)
                 VALUES (?, ?, ?, NULL, ?)'
            );
            $requete->execute([
                'Apex',
                'Fournitures',
                $email,
                password_hash('apex2026', PASSWORD_DEFAULT),
            ]);
            $fournisseurId = $this->pdo->lastInsertId();
        }

        $requete = $this->pdo->prepare(
            'INSERT INTO fournisseur (NumUsers, nomfourn, telephonefourn, siret, mailfourn)
             VALUES (?, ?, NULL, NULL, ?)'
        );
        $requete->execute([(int) $fournisseurId, 'Fournisseur de démonstration', $email]);

        return (int) $fournisseurId;
    }

    private function creerStatutsNecessaires(): void
    {
        $statuts = [
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
        $trouverStatut = $this->pdo->prepare(
            'SELECT Numstatut FROM statut WHERE libelle_statut = ? AND type_statut = ? LIMIT 1'
        );
        $creerStatut = $this->pdo->prepare(
            'INSERT INTO statut (libelle_statut, type_statut) VALUES (?, ?)'
        );
        $creerEtatEquipement = $this->pdo->prepare(
            'INSERT IGNORE INTO etat_equipement (Numstatut) VALUES (?)'
        );
        $creerStatutMaintenance = $this->pdo->prepare(
            'INSERT IGNORE INTO statut_maintenance (Numstatut) VALUES (?)'
        );

        foreach ($statuts as [$libelle, $type, $table]) {
            $trouverStatut->execute([$libelle, $type]);
            $statutId = $trouverStatut->fetchColumn();

            if ($statutId === false) {
                $creerStatut->execute([$libelle, $type]);
                $statutId = $this->pdo->lastInsertId();
            }

            if ($table === 'etat_equipement') {
                $creerEtatEquipement->execute([(int) $statutId]);
            } else {
                $creerStatutMaintenance->execute([(int) $statutId]);
            }
        }
    }

    private function creerTypesNecessaires(): void
    {
        $types = [
            ['Cardio-training', 'Équipements de cardio-training'],
            ['Musculation', 'Appareils et accessoires de musculation'],
            ['Accessoire fitness', 'Accessoires pour les activités fitness'],
        ];
        $trouverType = $this->pdo->prepare(
            'SELECT Numtypeequip FROM type_equipement WHERE libelletypeequip = ? LIMIT 1'
        );
        $creerType = $this->pdo->prepare(
            'INSERT INTO type_equipement (libelletypeequip, descriptionequip) VALUES (?, ?)'
        );

        foreach ($types as [$libelle, $description]) {
            $trouverType->execute([$libelle]);

            if ($trouverType->fetchColumn() === false) {
                $creerType->execute([$libelle, $description]);
            }
        }
    }

    private function ajouterEquipementsManquants(int $fournisseurId): int
    {
        $trouverStatutDisponible = $this->pdo->prepare(
            "SELECT Numstatut FROM statut
             WHERE libelle_statut = 'Disponible' AND type_statut = 'EQUIPEMENT' LIMIT 1"
        );
        $trouverStatutDisponible->execute();
        $statutDisponible = (int) $trouverStatutDisponible->fetchColumn();

        $equipements = [
            ['Tapis de course', 'RUN-500', 1899.00, 'Cardio-training'],
            ['Vélo elliptique', 'ELLIPTIC-300', 1299.00, 'Cardio-training'],
            ['Vélo d’appartement', 'BIKE-200', 799.00, 'Cardio-training'],
            ['Rameur', 'ROW-400', 999.00, 'Cardio-training'],
            ['Presse à jambes', 'LEG-PRESS-01', 2499.00, 'Musculation'],
            ['Banc de musculation', 'BENCH-02', 499.00, 'Musculation'],
            ['Haltères (lot)', 'DUMBBELL-SET', 350.00, 'Musculation'],
            ['Tapis de fitness', 'FIT-MAT-01', 29.00, 'Accessoire fitness'],
        ];
        $trouverEquipement = $this->pdo->prepare(
            'SELECT Numequip FROM equipement WHERE nomequip = ? AND modele = ? LIMIT 1'
        );
        $trouverType = $this->pdo->prepare(
            'SELECT Numtypeequip FROM type_equipement WHERE libelletypeequip = ? LIMIT 1'
        );
        $creerEquipement = $this->pdo->prepare(
            'INSERT INTO equipement
             (nomequip, marque, modele, date_achatequip, prix_achatequip, Numtypeequip, NumUsers_fourn, Numstatut)
             VALUES (?, ?, ?, CURRENT_DATE, ?, ?, ?, ?)'
        );
        $nombreAjoute = 0;

        foreach ($equipements as [$nom, $modele, $prix, $type]) {
            $trouverEquipement->execute([$nom, $modele]);

            if ($trouverEquipement->fetchColumn() !== false) {
                continue;
            }

            $trouverType->execute([$type]);
            $typeId = $trouverType->fetchColumn();

            if ($typeId === false || $statutDisponible === 0) {
                throw new RuntimeException(
                    'Les types ou statuts nécessaires à l’inventaire de démonstration sont absents.'
                );
            }

            $creerEquipement->execute([
                $nom,
                'Apex Fitness',
                $modele,
                $prix,
                (int) $typeId,
                $fournisseurId,
                $statutDisponible,
            ]);
            $nombreAjoute++;
        }

        return $nombreAjoute;
    }
}
