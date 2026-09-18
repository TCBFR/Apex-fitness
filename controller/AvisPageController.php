<?php

require_once __DIR__ . '/AvisController.php';

final class AvisPageController
{
    private AvisController $controller;

    public function __construct(?AvisController $controller = null)
    {
        $this->controller = $controller ?? new AvisController();
    }

    public function getAll(): array
    {
        return $this->controller->index();
    }

    public function set(int $userId, int $note, string $commentaire): void
    {
        $this->controller->creer($userId, $note, $commentaire);
    }

    public function getAllFiltered(array $avis, ?int $note): array
    {
        if ($note === null) {
            return $avis;
        }
        return array_values(array_filter($avis, static fn ($item) => (int) $item['noteavis'] === $note));
    }
}
