<?php

require_once __DIR__ . '/../Model/Avis.php';

final class AvisController
{
    private Avis $avis;

    public function __construct(?Avis $avis = null)
    {
        $this->avis = $avis ?? new Avis();
    }

    public function index(): array
    {
        return $this->avis->publies();
    }

    public function creer(int $numUtilisateur, int $note, string $commentaire): int
    {
        return $this->avis->creer($numUtilisateur, $note, trim($commentaire));
    }

    public function repondre(int $numInteraction, string $reponse): void
    {
        $this->avis->repondre($numInteraction, trim($reponse));
    }
}
