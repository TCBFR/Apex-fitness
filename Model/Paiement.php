<?php

require_once __DIR__ . '/Model.php';

final class Paiement extends Model
{
    public function offres(): array
    {
        return $this->pdo->query(
            'SELECT ab.Numabo, ab.libelle_abo, ab.type_abo,
                    f.prix_mensuel_reduit, b.prix_annuel, p.prix_mensuel_plus
             FROM abonnement ab
             LEFT JOIN freemium f ON f.Numabo = ab.Numabo
             LEFT JOIN basic b ON b.Numabo = ab.Numabo
             LEFT JOIN abonnement_plus p ON p.Numabo = ab.Numabo
             WHERE ab.actif_abo = 1 ORDER BY ab.Numabo'
        )->fetchAll();
    }

    public function payer(int $numUtilisateur, int $numAbonnement, string $moyen, bool $changement = false): float
    {
        if (!in_array($moyen, ['CB', 'Virement', 'Especes', 'Prelevement'], true)) {
            throw new InvalidArgumentException('Moyen de paiement invalide.');
        }

        $this->assureAdherent($numUtilisateur);

        $this->pdo->beginTransaction();
        try {
            $offre = $this->offre($numAbonnement);
            if (!$offre) {
                throw new RuntimeException('Cette formule n’existe pas.');
            }

            $montant = $this->montant($offre);
            $abonnementEnCours = $this->abonnementEnCours($numUtilisateur);
            $dateDebut = date('Y-m-d');
            $dateFin = $this->dateFinPourAbonnement($numAbonnement, $dateDebut);

            if ($abonnementEnCours && $changement) {
                $dateFinAncienne = date('Y-m-d', strtotime('last day of this month'));
                $this->pdo->prepare(
                    "UPDATE souscrire SET statut_souscription = 'Resiliee', date_fin = ?
                     WHERE NumUsers = ? AND statut_souscription = 'En cours'"
                )->execute([$dateFinAncienne, $numUtilisateur]);
                $dateDebut = date('Y-m-d', strtotime('first day of next month'));
                $dateFin = $this->dateFinPourAbonnement($numAbonnement, $dateDebut);
            } elseif ($abonnementEnCours) {
                $this->pdo->prepare(
                    "UPDATE souscrire SET statut_souscription = 'Terminee'
                     WHERE NumUsers = ? AND statut_souscription = 'En cours'"
                )->execute([$numUtilisateur]);
            }

            $this->pdo->prepare(
                "INSERT INTO souscrire (NumUsers, Numabo, date_debut, date_fin, statut_souscription)
                 VALUES (?, ?, ?, ?, 'En cours')"
            )->execute([$numUtilisateur, $numAbonnement, $dateDebut, $dateFin]);

            $this->pdo->prepare(
                'INSERT INTO paiement (montant, moyen_paiement, NumUsers, Numabo) VALUES (?, ?, ?, ?)'
            )->execute([$montant, $moyen, $numUtilisateur, $numAbonnement]);

            $actif = $this->pdo->query(
                "SELECT Numstatut FROM statut WHERE type_statut = 'ADHERENT' AND libelle_statut = 'Actif'"
            )->fetchColumn();
            $this->pdo->prepare(
                'UPDATE adherent SET Numstatut = ?, Date_finadh = ? WHERE NumUsers = ?'
            )->execute([(int) $actif, $dateFin, $numUtilisateur]);

            $this->pdo->commit();
            return $montant;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function assureAdherent(int $numUtilisateur): void
    {
        $exists = $this->pdo->prepare('SELECT 1 FROM adherent WHERE NumUsers = ?');
        $exists->execute([$numUtilisateur]);
        if ($exists->fetch()) {
            return;
        }

        $actif = (int) $this->pdo->query(
            "SELECT Numstatut FROM statut WHERE type_statut = 'ADHERENT' AND libelle_statut = 'Actif'"
        )->fetchColumn();

        $this->pdo->prepare(
            'INSERT INTO adherent (NumUsers, matriculeadh, Date_inscriptionadh, Date_finadh, Numstatut)
             VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), ?)'
        )->execute([
            $numUtilisateur,
            'ADH-' . str_pad((string) $numUtilisateur, 4, '0', STR_PAD_LEFT),
            $actif,
        ]);
    }

    private function abonnementEnCours(int $numUtilisateur): ?array
    {
        $st = $this->pdo->prepare(
            "SELECT NumUsers, Numabo, date_debut, date_fin, statut_souscription
             FROM souscrire
             WHERE NumUsers = ? AND statut_souscription = 'En cours'
             ORDER BY date_debut DESC LIMIT 1"
        );
        $st->execute([$numUtilisateur]);
        $ligne = $st->fetch();
        return $ligne ?: null;
    }

    private function dateFinPourAbonnement(int $numAbonnement, string $dateDebut): string
    {
        $offre = $this->offre($numAbonnement);
        if (!$offre) {
            throw new RuntimeException('Cette formule n’existe pas.');
        }

        $dt = new DateTimeImmutable($dateDebut);
        if ($offre['type_abo'] === 'BASIC') {
            return $dt->modify('+12 months')->format('Y-m-d');
        }

        return $dt->modify('+1 month')->format('Y-m-d');
    }

    private function offre(int $numAbonnement): ?array
    {
        $requete = $this->pdo->prepare(
            'SELECT ab.Numabo, ab.libelle_abo, ab.type_abo,
                    f.prix_mensuel_reduit, b.prix_annuel, p.prix_mensuel_plus
             FROM abonnement ab
             LEFT JOIN freemium f ON f.Numabo = ab.Numabo
             LEFT JOIN basic b ON b.Numabo = ab.Numabo
             LEFT JOIN abonnement_plus p ON p.Numabo = ab.Numabo
             WHERE ab.Numabo = ? AND ab.actif_abo = 1'
        );
        $requete->execute([$numAbonnement]);
        return $requete->fetch() ?: null;
    }

    private function montant(array $offre): float
    {
        return match ($offre['type_abo']) {
            'FREEMIUM' => (float) $offre['prix_mensuel_reduit'],
            'BASIC' => (float) $offre['prix_annuel'],
            'PLUS' => (float) $offre['prix_mensuel_plus'],
            default => throw new RuntimeException('Tarif de formule introuvable.'),
        };
    }
}
