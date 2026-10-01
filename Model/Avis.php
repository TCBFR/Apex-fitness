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
            "SELECT a.Numavis AS NumInteraction, a.noteavis, a.commentaire, a.reponse,
                a.date_avis AS date_interaction, u.prenomUsers, u.nomUsers
             FROM avis a
             JOIN utilisateur u ON u.NumUsers = a.NumAdh
             ORDER BY a.date_avis DESC"
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

        $avis = $this->pdo->prepare(
            'INSERT INTO avis (noteavis, commentaire, NumAdh) VALUES (?, ?, ?)'
        );
        $avis->execute([$note, $commentaire, $numUtilisateur]);
        return (int) $this->pdo->lastInsertId();
    }

    public function repondre(int $numInteraction, string $reponse): void
    {
        $this->assureColonneReponse();
        $reponse = trim($reponse);

        if ($reponse === '') {
            throw new InvalidArgumentException('La réponse ne peut pas être vide.');
        }

        $st = $this->pdo->prepare('UPDATE avis SET reponse = ? WHERE Numavis = ?');
        $st->execute([$reponse, $numInteraction]);

        if ($st->rowCount() === 0) {
            throw new RuntimeException('Avis introuvable.');
        }
    }
}
