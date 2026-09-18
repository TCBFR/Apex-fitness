<?php

require_once __DIR__ . '/Model.php';

final class Faq extends Model
{
    public function publiees(): array
    {
        return $this->pdo->query(
            "SELECT f.*, i.date_interaction
             FROM faq f
             JOIN interaction_adherent i ON i.NumInteraction = f.NumInteraction
             WHERE f.statut_faq = 'Publiee'
             ORDER BY f.categorie, f.NumInteraction"
        )->fetchAll();
    }

    public function toutes(): array
    {
        return $this->pdo->query(
            'SELECT f.*, i.date_interaction, u.nomUsers, u.prenomUsers
             FROM faq f
             JOIN interaction_adherent i ON i.NumInteraction = f.NumInteraction
             LEFT JOIN utilisateur u ON u.NumUsers = i.NumUsers
             ORDER BY (f.statut_faq = \'En attente\') DESC, i.date_interaction DESC'
        )->fetchAll();
    }

    public function creer(int $numUtilisateur, string $question): int
    {
        $this->pdo->beginTransaction();
        try {
            $interaction = $this->pdo->prepare(
                'INSERT INTO interaction_adherent (NumUsers, type_interaction) VALUES (?, ?)'
            );
            $interaction->execute([$numUtilisateur, 'FAQ']);
            $numInteraction = (int) $this->pdo->lastInsertId();

            $faq = $this->pdo->prepare(
                "INSERT INTO faq (NumInteraction, question, statut_faq, categorie)
                 VALUES (?, ?, 'En attente', 'General')"
            );
            $faq->execute([$numInteraction, $question]);
            $this->pdo->commit();
            return $numInteraction;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function repondre(int $numInteraction, string $reponse, string $categorie): void
    {
        $statut = $reponse === '' ? 'En attente' : 'Publiee';
        $requete = $this->pdo->prepare(
            'UPDATE faq SET reponse = ?, statut_faq = ?, categorie = ? WHERE NumInteraction = ?'
        );
        $requete->execute([$reponse === '' ? null : $reponse, $statut, $categorie ?: 'General', $numInteraction]);
    }

    public function supprimer(int $numInteraction): void
    {
        $requete = $this->pdo->prepare('DELETE FROM interaction_adherent WHERE NumInteraction = ?');
        $requete->execute([$numInteraction]);
    }
}
