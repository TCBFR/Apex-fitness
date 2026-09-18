<?php

require_once __DIR__ . '/../Model/Faq.php';

final class FaqController
{
    private Faq $faq;

    public function __construct(?Faq $faq = null)
    {
        $this->faq = $faq ?? new Faq();
    }

    public function index(): array
    {
        return $this->faq->publiees();
    }

    public function questions(): array
    {
        return $this->faq->toutes();
    }

    public function creer(int $numUtilisateur, string $question): int
    {
        $question = trim($question);
        if (mb_strlen($question) < 10) {
            throw new InvalidArgumentException('Formulez une question de 10 caractères minimum.');
        }
        return $this->faq->creer($numUtilisateur, $question);
    }

    public function repondre(int $numInteraction, string $reponse, string $categorie): void
    {
        $this->faq->repondre($numInteraction, trim($reponse), trim($categorie));
    }

    public function supprimer(int $numInteraction): void
    {
        $this->faq->supprimer($numInteraction);
    }
}
