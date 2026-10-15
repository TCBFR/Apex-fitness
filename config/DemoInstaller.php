<?php

declare(strict_types=1);

require_once __DIR__ . '/../qr_code/model/qr_code.php';

final class DemoInstaller
{
    private const DEMO_PASSWORD = 'apex2026';

    private const DEMO_EMPLOYEES = [
        [
            'nom' => 'Moreau',
            'prenom' => 'Lina',
            'email' => 'lina.moreau@apex.fr',
            'matriculeEmploye' => 'DEMO-EMP-7',
            'fonction' => 'Coach',
            'adherent' => true,
            'matriculeAdherent' => 'DEMO-ADH-7',
        ],
        [
            'nom' => 'Petit',
            'prenom' => 'Noah',
            'email' => 'noah.petit@apex.fr',
            'matriculeEmploye' => 'DEMO-EMP-8',
            'fonction' => 'Accueil',
            'adherent' => false,
            'matriculeAdherent' => null,
        ],
    ];

    public function __construct(private PDO $pdo)
    {
    }

    public function installer(): array
    {
        $this->verifierSchema();
        $this->creerTableComptesDemo();

        $hashMotDePasse = password_hash(self::DEMO_PASSWORD, PASSWORD_DEFAULT);
        $rapport = [
            $this->initialiserMotsDePasse($hashMotDePasse),
        ];

        $this->creerEmployesDemo($hashMotDePasse);
        $this->marquerComptesDemo();
        $rapport[] = 'Deux comptes de démonstration (employé seul et employée + adhérente) créés ou vérifiés.';
        $rapport[] = $this->initialiserInventaire();

        return [
            'motDePasse' => self::DEMO_PASSWORD,
            'rapport' => array_merge($rapport, $this->verifierDonneesDemo()),
        ];
    }

    private function verifierSchema(): void
    {
        $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $tablesAttendues = [
            'utilisateur',
            'adherent',
            'employe',
            'admin',
            'creneaux',
            'reservation',
            'abonnement',
            'fournisseur',
            'fonction',
            'statut',
            'etat_equipement',
            'statut_maintenance',
            'type_equipement',
            'equipement',
        ];
        $tablesManquantes = array_diff($tablesAttendues, $tables);

        if ($tablesManquantes !== []) {
            throw new RuntimeException(
                'Tables manquantes : ' . implode(', ', $tablesManquantes) .
                '. Importez d’abord sql/apex_fitness.sql.'
            );
        }
    }

    private function creerTableComptesDemo(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS compte_demo (
                NumUsers INT PRIMARY KEY,
                FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );
    }

    private function initialiserMotsDePasse(string $hashMotDePasse): string
    {
        $requete = $this->pdo->prepare('UPDATE utilisateur SET passwordUsers = ?');
        $requete->execute([$hashMotDePasse]);

        return $requete->rowCount() . ' comptes initialisés avec le mot de passe de démonstration.';
    }

    private function creerEmployesDemo(string $hashMotDePasse): void
    {
        foreach (self::DEMO_EMPLOYEES as $employe) {
            $utilisateurId = $this->trouverOuCreerUtilisateur($employe, $hashMotDePasse);
            $fonctionId = $this->trouverOuCreerFonction($employe['fonction']);

            $this->creerEmployeSiAbsent($utilisateurId, $employe['matriculeEmploye'], $fonctionId);

            if ($employe['adherent']) {
                $this->creerAdherentSiAbsent($utilisateurId, $employe['matriculeAdherent']);
            }
        }
    }

    private function trouverOuCreerUtilisateur(array $employe, string $hashMotDePasse): int
    {
        $requete = $this->pdo->prepare('SELECT NumUsers FROM utilisateur WHERE mailUsers = ?');
        $requete->execute([$employe['email']]);
        $utilisateurId = $requete->fetchColumn();

        if ($utilisateurId !== false) {
            return (int) $utilisateurId;
        }

        $requete = $this->pdo->prepare(
            'INSERT INTO utilisateur (nomUsers, prenomUsers, mailUsers, telUsers, passwordUsers)
             VALUES (?, ?, ?, NULL, ?)'
        );
        $requete->execute([
            $employe['nom'],
            $employe['prenom'],
            $employe['email'],
            $hashMotDePasse,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function trouverOuCreerFonction(string $libelle): int
    {
        $requete = $this->pdo->prepare('SELECT Numfonc FROM fonction WHERE libellefonc = ?');
        $requete->execute([$libelle]);
        $fonctionId = $requete->fetchColumn();

        if ($fonctionId !== false) {
            return (int) $fonctionId;
        }

        $requete = $this->pdo->prepare('INSERT INTO fonction (libellefonc) VALUES (?)');
        $requete->execute([$libelle]);

        return (int) $this->pdo->lastInsertId();
    }

    private function creerEmployeSiAbsent(int $utilisateurId, string $matricule, int $fonctionId): void
    {
        $requete = $this->pdo->prepare('SELECT NumUsers FROM employe WHERE NumUsers = ?');
        $requete->execute([$utilisateurId]);

        if ($requete->fetchColumn() !== false) {
            return;
        }

        $requete = $this->pdo->prepare(
            'INSERT INTO employe (NumUsers, matriculeemp, Date_embaucheemp, Numfonc)
             VALUES (?, ?, CURRENT_DATE, ?)'
        );
        $requete->execute([$utilisateurId, $matricule, $fonctionId]);
    }

    private function creerAdherentSiAbsent(int $utilisateurId, string $matricule): void
    {
        $requete = $this->pdo->prepare('SELECT NumUsers FROM adherent WHERE NumUsers = ?');
        $requete->execute([$utilisateurId]);

        if ($requete->fetchColumn() !== false) {
            (new GenerateurQrCode())->assurerPourAdherent($this->pdo, $utilisateurId, $matricule);
            return;
        }

        $requete = $this->pdo->prepare(
            'INSERT INTO adherent (NumUsers, matriculeadh, Date_inscriptionadh)
             VALUES (?, ?, CURRENT_DATE)'
        );
        $requete->execute([$utilisateurId, $matricule]);
        (new GenerateurQrCode())->assurerPourAdherent($this->pdo, $utilisateurId, $matricule);
    }

    private function marquerComptesDemo(): void
    {
        $requete = $this->pdo->prepare(
            'INSERT IGNORE INTO compte_demo (NumUsers)
             SELECT NumUsers FROM utilisateur WHERE mailUsers IN (?, ?)'
        );
        $requete->execute(['lina.moreau@apex.fr', 'noah.petit@apex.fr']);
    }

    private function initialiserInventaire(): string
    {
        $nombreAjoute = (new InventaireDemo($this->pdo))->initialiser();

        return $nombreAjoute . ' appareil(s) de fitness de démonstration ajouté(s) à l’inventaire.';
    }

    private function verifierDonneesDemo(): array
    {
        $cumulEmployeEtAdherent = (int) $this->pdo->query(
            'SELECT COUNT(*) FROM employe e JOIN adherent a ON a.NumUsers = e.NumUsers'
        )->fetchColumn();
        $nombreCreneaux = (int) $this->pdo->query('SELECT COUNT(*) FROM creneaux')->fetchColumn();
        $nombreReservations = (int) $this->pdo->query('SELECT COUNT(*) FROM reservation')->fetchColumn();

        return [
            $cumulEmployeEtAdherent . ' compte(s) cumulant EMPLOYE + ADHERENT (héritage T).',
            $nombreCreneaux . ' créneau(x) et ' . $nombreReservations . ' réservation(s) de démonstration.',
        ];
    }
}
