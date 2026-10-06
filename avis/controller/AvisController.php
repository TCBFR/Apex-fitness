<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/AvisDomain.php';

/** Coordonne les opérations sur les avis et leur affichage public. */
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

    public function filtrerParNote(array $avis, ?int $note): array
    {
        if ($note === null) {
            return $avis;
        }

        return array_values(array_filter(
            $avis,
            static fn(array $item): bool => (int) $item['noteavis'] === $note
        ));
    }

    public function traiterCrud(): array
    {
        return (new EntityController(Avis::class))->traiter();
    }
}
