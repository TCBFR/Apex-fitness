<?php

require_once __DIR__ . '/Model.php';

final class Avis extends Model
{
    private function assureColonneReponse(): void
    {
        $colonne = $this->pdo->query("SHOW COLUMNS FROM avis LIKE 'reponse'")->fetch();
        if (!$colonne) {
            $this->pdo->exec("ALTER TABLE avis ADD COLUMN reponse TEXT NULL AFTER commentaire");
        }
    }

    public function publies(): array
    {
        $this->assureColonneReponse();

        return $this->pdo->query(
            "SELECT a.NumInteraction, a.noteavis, a.commentaire, a.reponse, i.date_interaction,
                    u.prenomUsers, u.nomUsers
             FROM avis a
             JOIN interaction_adherent i ON i.NumInteraction = a.NumInteraction
             JOIN adherent ad ON ad.NumUsers = i.NumUsers
             JOIN utilisateur u ON u.NumUsers = ad.NumUsers
             ORDER BY i.date_interaction DESC"
        )->fetchAll();
    }

    public function creer(int $numUtilisateur, int $note, string $commentaire): int
    {
        if ($note < 1 || $note > 5) {
            throw new InvalidArgumentException('La note doit être comprise entre 1 et 5.');
        }
        if (mb_strlen($commentaire) < 10) {
            throw new InvalidArgumentException('Votre avis doit contenir au moins 10 caractères.');
        }

        $this->pdo->beginTransaction();
        try {
            $interaction = $this->pdo->prepare(
                "INSERT INTO interaction_adherent (NumUsers, type_interaction)
                 VALUES (?, 'AVIS')"
            );
            $interaction->execute([$numUtilisateur]);
            $numInteraction = (int) $this->pdo->lastInsertId();

            $avis = $this->pdo->prepare(
                'INSERT INTO avis (NumInteraction, noteavis, commentaire) VALUES (?, ?, ?)'
            );
            $avis->execute([$numInteraction, $note, $commentaire]);
            $this->pdo->commit();
            return $numInteraction;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function repondre(int $numInteraction, string $reponse): void
    {
        $this->assureColonneReponse();
        $reponse = trim($reponse);

        if ($reponse === '') {
            throw new InvalidArgumentException('La réponse ne peut pas être vide.');
        }

        $st = $this->pdo->prepare('UPDATE avis SET reponse = ? WHERE NumInteraction = ?');
        $st->execute([$reponse, $numInteraction]);

        if ($st->rowCount() === 0) {
            throw new RuntimeException('Avis introuvable.');
        }
    }
}
