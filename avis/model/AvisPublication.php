<?php

declare(strict_types=1);

trait AvisPublication
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
            "SELECT a.NumInteraction, a.noteavis, a.commentaire, a.reponse,
                    i.date_interaction, u.prenomUsers, u.nomUsers
             FROM avis a
             JOIN interaction_adherent i ON i.NumInteraction = a.NumInteraction
             JOIN utilisateur u ON u.NumUsers = i.NumUsers
             WHERE i.type_interaction = 'AVIS'
             ORDER BY i.date_interaction DESC"
        )->fetchAll();
    }

    public function avisDeAdherent(int $numUtilisateur): ?array
    {
        $this->assureColonneReponse();
        $requete = $this->pdo->prepare(
            "SELECT i.NumInteraction, i.date_interaction, a.noteavis, a.commentaire, a.reponse
             FROM interaction_adherent i
             JOIN avis a ON a.NumInteraction = i.NumInteraction
             WHERE i.NumUsers = ? AND i.type_interaction = 'AVIS'
             ORDER BY i.date_interaction DESC LIMIT 1"
        );
        $requete->execute([$numUtilisateur]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function creer(int $numUtilisateur, int $note, string $commentaire): int
    {
        return (new self($numUtilisateur, 0, $note, $commentaire, $this->pdo))->createReview($this->pdo);
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
