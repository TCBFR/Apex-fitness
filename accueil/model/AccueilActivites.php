<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/function.php';
require_once __DIR__ . '/../../activite/model/ActiviteCatalogue.php';

/** Lit les activités affichées sur la page d’accueil. */
final class AccueilActivites
{
    public function toutes(): array
    {
        $pdo = db();
        ActiviteCatalogue::assurer($pdo);
        return ActiviteCatalogue::toutes($pdo);
    }
}
