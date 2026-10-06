<?php

declare(strict_types=1);

require_once __DIR__ . '/../../interaction_adherent/model/interaction_adherent.php';

final class Avis extends InteractionAdherent
{
    protected const TABLE = 'avis';

    private int $numReservation;
    private int $note;
    private string $commentaire;
    private ?string $reponse;

    public function __construct(
        int $numUtilisateur = 0,
        int $numReservation = 0,
        int $note = 0,
        string $commentaire = '',
        ?PDO $pdo = null
    ) {
        parent::__construct($numUtilisateur, 'AVIS', $pdo);
        $this->numReservation = $numReservation;
        $this->note = $note;
        $this->commentaire = $commentaire;
        $this->reponse = null;
    }

    public function createReview(PDO $pdo): int
    {
        $this->assureReservationOptionnelle($pdo);

        if ($this->note < 1 || $this->note > 5) {
            throw new InvalidArgumentException('La note doit être comprise entre 1 et 5.');
        }
        if (mb_strlen(trim($this->commentaire)) < 10) {
            throw new InvalidArgumentException('Votre avis doit contenir au moins 10 caractères.');
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
