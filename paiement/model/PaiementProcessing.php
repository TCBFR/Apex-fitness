<?php

declare(strict_types=1);

trait PaiementProcessing
{
    public function payer(
        int $numUtilisateur,
        int $numAbonnement,
        string $moyen,
        bool $changement,
        string $versionContrat,
        string $contenuContrat,
        string $empreinteContrat,
        int $dateAcceptation
    ): float {
        if (!in_array($moyen, ['CB', 'Virement', 'Especes', 'Prelevement'], true)) {
            throw new InvalidArgumentException('Moyen de paiement invalide.');
        }
        if ($dateAcceptation > time() || $dateAcceptation < time() - 1800) {
            throw new InvalidArgumentException('Votre acceptation a expiré. Veuillez relire et valider le contrat.');
        }

        $this->assureAdherent($numUtilisateur);

        $this->pdo->beginTransaction();
        try {
            $offre = $this->offre($numAbonnement);
            if (!$offre) {
                throw new RuntimeException('Cette formule n’existe pas.');
            }
            $contratAttendu = ContratAbonnement::contenu($offre, $changement);
            $empreinteAttendue = hash('sha256', $contratAttendu);
            if (
                $versionContrat !== ContratAbonnement::VERSION
                || !hash_equals($empreinteAttendue, $empreinteContrat)
                || !hash_equals($contratAttendu, $contenuContrat)
            ) {
                throw new RuntimeException('Le contrat a été modifié. Veuillez le relire et l’accepter à nouveau.');
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
            $paymentId = (int) $this->pdo->lastInsertId();
            $this->pdo->prepare(
                'INSERT INTO acceptation_contrat_abonnement
                    (NumUsers, Numabo, Numpaiement, version_contrat, contenu_contrat, date_acceptation)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $numUtilisateur,
                $numAbonnement,
                $paymentId,
                $versionContrat,
                $contenuContrat,
                date('Y-m-d H:i:s', $dateAcceptation),
            ]);

            $this->pdo->prepare(
                'UPDATE adherent SET Date_finadh = ? WHERE NumUsers = ?'
            )->execute([$dateFin, $numUtilisateur]);

            $this->pdo->commit();
            return $montant;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
