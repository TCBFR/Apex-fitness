<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/function.php';

/** Lit les activités affichées sur la page d’accueil. */
final class AccueilActivites
{
    public function toutes(): array
    {
        return db()->query(
            'SELECT titre, date_heure_debut,
                    TIMESTAMPDIFF(MINUTE, date_heure_debut, date_heure_fin) AS duree_minutes,
                    capacite_max, description
             FROM activite
             WHERE date_heure_debut >= NOW()
             ORDER BY date_heure_debut
             LIMIT 6'
        )->fetchAll();
    }
}
