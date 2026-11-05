<?php

declare(strict_types=1);

trait AvisReviewCreation
{
    public function createReview(PDO $pdo): int
    {
        $this->assureReservationOptionnelle($pdo);

        if ($this->note < 1 || $this->note > 5) {
            throw new InvalidArgumentException('La note doit être comprise entre 1 et 5.');
        }
        if (mb_strlen(trim($this->commentaire)) < 10) {
            throw new InvalidArgumentException('Votre avis doit contenir au moins 10 caractères.');
        }
        if (mb_strlen(trim($this->commentaire)) > 100) {
            throw new InvalidArgumentException('Votre avis ne peut pas dépasser 100 caractères.');
        }

        $pdo->beginTransaction();
        try {
            $adherent = $pdo->prepare('SELECT NumUsers FROM adherent WHERE NumUsers = ? FOR UPDATE');
            $adherent->execute([$this->numUtilisateur]);
            if (!$adherent->fetchColumn()) {
                throw new RuntimeException('Seul un adhérent peut publier un avis.');
            }

            $avisExistants = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM interaction_adherent i
                 JOIN avis a ON a.NumInteraction = i.NumInteraction
                 WHERE i.NumUsers = ? AND i.type_interaction = 'AVIS'"
            );
            $avisExistants->execute([$this->numUtilisateur]);
            if ((int) $avisExistants->fetchColumn() > 0) {
                throw new RuntimeException('Vous avez déjà publié un avis.');
            }

            $numReservation = $this->numReservation;
            if ($numReservation < 1) {
                $reservation = $pdo->prepare(
                    'SELECT r.Numres
                     FROM reservation r
                     LEFT JOIN avis a ON a.Numres = r.Numres
                     WHERE r.NumUsers = ? AND a.NumInteraction IS NULL
                     ORDER BY r.Numres DESC LIMIT 1 FOR UPDATE'
                );
                $reservation->execute([$this->numUtilisateur]);
                $numReservation = $reservation->fetchColumn();
                $numReservation = $numReservation === false ? null : (int) $numReservation;
            }

            $id = parent::createInteraction($pdo);
            $requete = $pdo->prepare(
                'INSERT INTO avis (NumInteraction, noteavis, commentaire, reponse, Numres)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $requete->execute([$id, $this->note, trim($this->commentaire), $this->reponse, $numReservation]);
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    private function assureReservationOptionnelle(PDO $pdo): void
    {
        $colonne = $pdo->query("SHOW COLUMNS FROM avis LIKE 'Numres'")->fetch(PDO::FETCH_ASSOC);
        if (!$colonne) {
            throw new RuntimeException('La colonne Numres est absente de la table avis.');
        }
        if ($colonne['Null'] !== 'YES') {
            $pdo->exec('ALTER TABLE avis MODIFY Numres INT NULL');
        }
    }
}
