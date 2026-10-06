<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
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

    public function payer(int $numUtilisateur, int $numAbonnement, string $moyen, bool $changement = false): float
    {
        return $this->paiement->payer($numUtilisateur, $numAbonnement, $moyen, $changement);
    }

    public function traiterCrud(): array
    {
        return (new EntityController(Paiement::class))->traiter();
    }
}
