<?php

declare(strict_types=1);

trait FaqPersistence
{
    public function createFaq(PDO $pdo): int
    {
        if (trim($this->question) === '') {
            throw new InvalidArgumentException('La question est obligatoire.');
        }
        $pdo->beginTransaction();
        try {
            $id = parent::createInteraction($pdo);
            $requete = $pdo->prepare(
                'INSERT INTO faq (NumInteraction, question, reponse, statut_faq, categorie)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $requete->execute([$id, trim($this->question), $this->reponse, $this->statut, $this->categorie]);
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function updateFaq(PDO $pdo, int $id): void
    {
        $pdo->beginTransaction();
        try {
            parent::updateInteraction($pdo, $id);
            $requete = $pdo->prepare(
                'UPDATE faq SET question = ?, reponse = ?, statut_faq = ?, categorie = ?
                 WHERE NumInteraction = ?'
            );
            $requete->execute([$this->question, $this->reponse, $this->statut, $this->categorie, $id]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public static function findFaqById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare(
            'SELECT i.NumInteraction, i.date_interaction, i.type_interaction, i.NumUsers,
                    f.question, f.reponse, f.statut_faq, f.categorie
             FROM interaction_adherent i JOIN faq f ON f.NumInteraction = i.NumInteraction
             WHERE i.NumInteraction = ?'
        );
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findFaqs(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT i.NumInteraction, i.date_interaction, i.type_interaction, i.NumUsers,
                    f.question, f.reponse, f.statut_faq, f.categorie
             FROM interaction_adherent i JOIN faq f ON f.NumInteraction = i.NumInteraction
             ORDER BY i.date_interaction DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteFaq(PDO $pdo, int $id): void
    {
        InteractionAdherent::deleteInteraction($pdo, $id);
    }
}
