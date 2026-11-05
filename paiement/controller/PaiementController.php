<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/PaiementDomain.php';

/** Coordonne la consultation des offres et l’enregistrement des paiements. */
final class PaiementController
{
    private Paiement $paiement;

    public function __construct(?Paiement $paiement = null)
    {
        $this->paiement = $paiement ?? new Paiement();
    }

    public function offres(): array
    {
        return $this->paiement->offres();
    }

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
        return $this->paiement->payer(
            $numUtilisateur,
            $numAbonnement,
            $moyen,
            $changement,
            $versionContrat,
            $contenuContrat,
            $empreinteContrat,
            $dateAcceptation
        );
    }

    public function traiterCrud(): array
    {
        return (new ControleurGenerique(Paiement::class))->traiter();
    }
}
