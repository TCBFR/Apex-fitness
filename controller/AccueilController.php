<?php

final class AccueilController
{
    public function getAll(): array
    {
        return db()->query(
            'SELECT Numactivite,
        niveau,
        date_heure_debut,
        date_heure_fin,
        capacite_max,
        description
 FROM activite
 ORDER BY titre'
        )->fetchAll();
    }
}
