<?php

require_once __DIR__ . '/../model/FournisseurDomain.php';

final class FournisseurController
{
    public function tous(): array
    {
        return Fournisseur::tous();
    }

    public function trouver(int $id): ?array
    {
        return Fournisseur::findSupplierById(db(), $id);
    }

    public function utilisateursDisponibles(): array
    {
        return Fournisseur::utilisateurs();
    }

    public function enregistrer(array $donnees, ?int $id = null): void
    {
        Fournisseur::enregistrer($donnees, $id);
    }

    public function supprimer(int $id): void
    {
        Fournisseur::supprimer($id);
    }
}
