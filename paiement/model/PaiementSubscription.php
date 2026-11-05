<?php

declare(strict_types=1);

trait PaiementSubscription
{
    private function assureAdherent(int $numUtilisateur): void
    {
        $exists = $this->pdo->prepare('SELECT 1 FROM adherent WHERE NumUsers = ?');
        $exists->execute([$numUtilisateur]);
        if ($exists->fetch()) {
            $matricule = $this->pdo->prepare('SELECT matriculeadh FROM adherent WHERE NumUsers = ?');
            $matricule->execute([$numUtilisateur]);
            (new GenerateurQrCode())->assurerPourAdherent(
                $this->pdo,
                $numUtilisateur,
                (string) $matricule->fetchColumn()
            );
            return;
        }

        $matricule = 'ADH-' . str_pad((string) $numUtilisateur, 4, '0', STR_PAD_LEFT);
        $this->pdo->prepare(
            'INSERT INTO adherent (NumUsers, matriculeadh, Date_inscriptionadh, Date_finadh)
             VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH))'
        )->execute([$numUtilisateur, $matricule]);
        (new GenerateurQrCode())->assurerPourAdherent($this->pdo, $numUtilisateur, $matricule);
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
            $months = max(1, (int) ($offre['duree_engagement_mois'] ?? 12));
            return $dt->modify('+' . $months . ' months')->format('Y-m-d');
        }

        return $dt->modify('+1 month')->format('Y-m-d');
    }
}
