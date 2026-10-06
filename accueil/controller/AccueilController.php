<?php

require_once __DIR__ . '/../model/AccueilActivites.php';

/** Fournit les données nécessaires à la page d’accueil publique. */
final class AccueilController
{
    private AccueilActivites $activites;

    public function __construct(?AccueilActivites $activites = null)
    {
        $this->activites = $activites ?? new AccueilActivites();
    }

    public function getAll(): array
    {
        return $this->activites->toutes();
    }
}
