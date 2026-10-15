<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/function.php';

final class Notification
{
    public function __construct(private ?PDO $pdo = null)
    {
        $this->pdo ??= db();
    }

    public function pourAdherent(int $adherentId, int $limit = 20): array
    {
        $this->genererRappelsDus();

        $query = $this->pdo->prepare(
            'SELECT Numnotification AS id, type_notification AS type,
                    titre AS title, message, date_creation AS created_at,
                    date_lecture AS read_at
             FROM notification_adherent
             WHERE NumUsers = ?
             ORDER BY date_creation DESC, Numnotification DESC
             LIMIT ?'
        );
        $query->bindValue(1, $adherentId, PDO::PARAM_INT);
        $query->bindValue(2, $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function compterNonLues(int $adherentId): int
    {
        $query = $this->pdo->prepare(
            'SELECT COUNT(*) FROM notification_adherent
             WHERE NumUsers = ? AND date_lecture IS NULL'
        );
        $query->execute([$adherentId]);
        return (int)$query->fetchColumn();
    }

    public function preparerRappels(): int
    {
        return $this->genererRappelsDus();
    }

    public function marquerCommeLue(int $adherentId, int $notificationId): bool
    {
        $query = $this->pdo->prepare(
            'UPDATE notification_adherent
             SET date_lecture = NOW()
             WHERE Numnotification = ? AND NumUsers = ? AND date_lecture IS NULL'
        );
        $query->execute([$notificationId, $adherentId]);
        return $query->rowCount() > 0;
    }

    private function genererRappelsDus(): int
    {
        $created = $this->pdo->exec(
            "INSERT IGNORE INTO notification_adherent
                (NumUsers, Numres, type_notification, titre, message)
             SELECT r.NumUsers, r.Numres, 'RAPPEL_SEANCE', 'Rappel de séance',
                    CONCAT('Votre séance « ', a.titre, ' » commence le ',
                           DATE_FORMAT(a.date_heure_debut, '%d/%m à %Hh%i'), '.')
             FROM reservation r
             JOIN activite a ON a.Numactivite = r.Numactivite
             WHERE r.statut_reservation <> 'Annulee'
               AND a.date_heure_debut > NOW()
               AND a.date_heure_debut <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
               AND NOT EXISTS (
                   SELECT 1 FROM notification_adherent n
                   WHERE n.Numres = r.Numres AND n.type_notification = 'RAPPEL_SEANCE'
               )"
        );
        return $created === false ? 0 : $created;
    }
}
