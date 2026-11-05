<?php

declare(strict_types=1);

trait PlanningScheduleQueries
{
    public function capaciteSalleVerrouillee(int $numSalle): ?int
    {
        $st = $this->pdo->prepare('SELECT capacitesalle FROM salle WHERE Numsalle = ? FOR UPDATE');
        $st->execute([$numSalle]);
        $v = $st->fetchColumn();
        return $v === false ? null : (int) $v;
    }

    public function coachValide(int $numUsers): bool
    {
        $st = $this->pdo->prepare(
            "SELECT 1 FROM employe e
             JOIN fonction f ON f.Numfonc = e.Numfonc
             WHERE e.NumUsers = ? AND f.libellefonc <> 'Technicien maintenance'"
        );
        $st->execute([$numUsers]);
        return (bool) $st->fetchColumn();
    }

    public function conflit(int $numSalle, int $numCoach, string $debut, string $fin, ?int $exclure = null): bool
    {
        $st = $this->pdo->prepare(
            'SELECT COUNT(*) FROM activite
             WHERE (Numsalle = ? OR NumUsers = ?) AND date_heure_debut < ? AND date_heure_fin > ?
               AND (? IS NULL OR Numactivite <> ?)'
        );
        $st->execute([$numSalle, $numCoach, $fin, $debut, $exclure, $exclure]);
        return (int) $st->fetchColumn() > 0;
    }

    public function detailsCreneau(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM activite WHERE Numactivite = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function modifierCreneau(int $id, string $titre, string $type, string $debut, string $fin,
                                    int $capacite, int $salle, int $coach): void
    {
        $this->pdo->prepare(
            'UPDATE activite SET titre=?, type_activite=?, date_heure_debut=?, date_heure_fin=?,
                                 capacite_max=?, Numsalle=?, NumUsers=? WHERE Numactivite=?'
        )->execute([$titre, $type, $debut, $fin, $capacite, $salle, $coach, $id]);
    }

    public function insererCreneau(string $titre, string $type, string $debut, string $fin,
                                   int $capacite, int $numSalle, int $numCoach, ?string $idSerie): void
    {
        $this->pdo->prepare(
            'INSERT INTO activite (titre, type_activite, date_heure_debut, date_heure_fin,
                                   capacite_max, Numsalle, NumUsers, id_serie)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$titre, $type, $debut, $fin, $capacite, $numSalle, $numCoach, $idSerie]);
    }

    public function creneau(int $numActivite): ?array
    {
        $st = $this->pdo->prepare('SELECT Numactivite, id_serie FROM activite WHERE Numactivite = ?');
        $st->execute([$numActivite]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function supprimerCreneau(int $numActivite): int
    {
        $st = $this->pdo->prepare('DELETE FROM activite WHERE Numactivite = ?');
        $st->execute([$numActivite]);
        return $st->rowCount();
    }

    public function supprimerSerieAVenir(string $idSerie): int
    {
        $st = $this->pdo->prepare('DELETE FROM activite WHERE id_serie = ? AND date_heure_debut >= NOW()');
        $st->execute([$idSerie]);
        return $st->rowCount();
    }
}
