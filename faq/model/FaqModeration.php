<?php

declare(strict_types=1);

trait FaqModeration
{
    public function creer(int $numUtilisateur, string $question): int
    {
        $faq = new self($numUtilisateur, $question, $this->pdo);
        return $faq->createFaq($this->pdo);
    }

    public function repondre(int $numInteraction, string $reponse, string $categorie): void
    {
        $reponse = trim($reponse);
        $statut = $reponse === '' ? 'En attente' : 'Publiee';
        $requete = $this->pdo->prepare(
            'UPDATE faq SET reponse = ?, statut_faq = ?, categorie = ? WHERE NumInteraction = ?'
        );
        $requete->execute([$reponse === '' ? null : $reponse, $statut, $categorie ?: 'General', $numInteraction]);
    }

    public function supprimer(int $numInteraction): void
    {
        InteractionAdherent::deleteInteraction($this->pdo, $numInteraction);
    }
}
