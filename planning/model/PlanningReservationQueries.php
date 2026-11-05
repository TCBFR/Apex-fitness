<?php

declare(strict_types=1);

trait PlanningReservationQueries
{
    public function verrouillerCreneau(int $numActivite): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT Numactivite, titre, capacite_max, date_heure_debut
             FROM activite WHERE Numactivite = ? FOR UPDATE'
        );
        $st->execute([$numActivite]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function placesPrises(int $numActivite): int
    {
        $st = $this->pdo->prepare(
            "SELECT COUNT(*) FROM reservation
             WHERE Numactivite = ? AND statut_reservation <> 'Annulee'"
        );
        $st->execute([$numActivite]);
        return (int) $st->fetchColumn();
    }

    public function reservationExistante(int $numUsers, int $numActivite): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT Numres, statut_reservation FROM reservation
             WHERE NumUsers = ? AND Numactivite = ?'
        );
        $st->execute([$numUsers, $numActivite]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function reservationsDuMois(int $numUsers, string $dateCreneau): int
    {
        $st = $this->pdo->prepare(
            "SELECT COUNT(*) FROM reservation r
             JOIN activite a ON a.Numactivite = r.Numactivite
             WHERE r.NumUsers = ? AND r.statut_reservation <> 'Annulee'
               AND YEAR(a.date_heure_debut) = YEAR(?) AND MONTH(a.date_heure_debut) = MONTH(?)"
        );
        $st->execute([$numUsers, $dateCreneau, $dateCreneau]);
        return (int) $st->fetchColumn();
    }

    public function reactiverReservation(int $numRes): void
    {
        $this->pdo->prepare(
            "UPDATE reservation SET statut_reservation = 'Reservee', date_heure_reservation = NOW()
             WHERE Numres = ?"
        )->execute([$numRes]);
    }

    public function creerReservation(int $numUsers, int $numActivite): void
    {
        $this->pdo->prepare('INSERT INTO reservation (NumUsers, Numactivite) VALUES (?, ?)')
                  ->execute([$numUsers, $numActivite]);
    }

    public function reservationDeAdherent(int $numRes, int $numUsers): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT r.Numres, r.statut_reservation, a.date_heure_debut
             FROM reservation r
             JOIN activite a ON a.Numactivite = r.Numactivite
             WHERE r.Numres = ? AND r.NumUsers = ?'
        );
        $st->execute([$numRes, $numUsers]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function annulerReservation(int $numRes): void
    {
        $this->pdo->prepare("UPDATE reservation SET statut_reservation = 'Annulee' WHERE Numres = ?")
                  ->execute([$numRes]);
    }
}
