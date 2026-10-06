<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/FournisseurDomain.php';
require_once __DIR__ . '/../../employe/model/employe.php';

/** Coordonne les opérations d’administration des comptes employés. */
final class GestionController
{
    private Personnel $personnel;

    public function __construct(?Personnel $personnel = null)
    {
        $this->personnel = $personnel ?? new Personnel();
    }

    public function personnel(): array
    {
        return $this->personnel->tous();
    }

    public function fonctions(): array
    {
        return $this->personnel->fonctions();
    }

    public function modifierFonction(int $numUtilisateur, int $numFonction): void
    {
        if ($numUtilisateur < 1 || $numFonction < 1) {
            throw new InvalidArgumentException('Personnel ou fonction invalide.');
        }
        $this->personnel->modifierFonction($numUtilisateur, $numFonction);
    }

    public function supprimerEmploye(int $numUtilisateur): void
    {
        $this->personnel->supprimer($numUtilisateur);
    }

    public function traiterCrud(): array
    {
        return (new EntityController(Fournisseur::class))->traiter();
    }
}
