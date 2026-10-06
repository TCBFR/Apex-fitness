<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/function.php';

/** Lit les activités affichées sur la page d’accueil. */
final class AccueilActivites
{
    public function toutes(): array
    {
        return db()->query(
            'SELECT Numactivite, niveau, date_heure_debut, date_heure_fin,
                    capacite_max, description
             FROM activite
             ORDER BY titre'
        )->fetchAll();
    }
}
