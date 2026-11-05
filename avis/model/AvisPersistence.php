<?php

declare(strict_types=1);

trait AvisPersistence
{
    public function updateReview(PDO $pdo, int $id): void
    {
        $pdo->beginTransaction();
        try {
            parent::updateInteraction($pdo, $id);
            $requete = $pdo->prepare(
                'UPDATE avis SET noteavis = ?, commentaire = ?, reponse = ?, Numres = ?
                 WHERE NumInteraction = ?'
            );
            $requete->execute([$this->note, $this->commentaire, $this->reponse, $this->numReservation, $id]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public static function findReviewById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare(
            'SELECT i.NumInteraction, i.date_interaction, i.type_interaction, i.NumUsers,
                    a.noteavis, a.commentaire, a.reponse, a.Numres
             FROM interaction_adherent i JOIN avis a ON a.NumInteraction = i.NumInteraction
             WHERE i.NumInteraction = ?'
        );
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findReviews(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT i.NumInteraction, i.date_interaction, i.type_interaction, i.NumUsers,
                    a.noteavis, a.commentaire, a.reponse, a.Numres
             FROM interaction_adherent i JOIN avis a ON a.NumInteraction = i.NumInteraction
             ORDER BY i.date_interaction DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteReview(PDO $pdo, int $id): void
    {
        InteractionAdherent::deleteInteraction($pdo, $id);
    }
}
