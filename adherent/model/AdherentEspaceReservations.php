<?php

declare(strict_types=1);

trait AdherentEspaceReservations
{
    public function reservations(int $id, bool $avenir = true): array
    {
        $condition = $avenir ? 'AND a.date_heure_debut >= NOW()' : '';
        $q = $this->pdo->prepare(
            "SELECT r.Numres, r.statut_reservation, r.date_heure_reservation,
                    a.Numactivite, a.titre, a.type_activite, a.niveau,
                    a.date_heure_debut, a.date_heure_fin, a.capacite_max,
                    s.nomsalle, s.localisationsalle, coach.prenomUsers AS coach,
                    (SELECT COUNT(*) FROM reservation r2 WHERE r2.Numactivite=a.Numactivite
                       AND r2.statut_reservation <> 'Annulee') AS places_prises
             FROM reservation r JOIN activite a ON a.Numactivite=r.Numactivite
             JOIN salle s ON s.Numsalle=a.Numsalle
             JOIN utilisateur coach ON coach.NumUsers=a.NumUsers
             WHERE r.NumUsers=? AND r.statut_reservation <> 'Annulee' $condition
             ORDER BY a.date_heure_debut"
        );
        $q->execute([$id]);
        return $q->fetchAll();
    }

    public function historiqueReservations(int $id): array
    {
        $q = $this->pdo->prepare(
            "SELECT r.Numres, a.Numactivite, a.titre, a.type_activite, a.date_heure_debut,
                    a.date_heure_fin, s.nomsalle, coach.prenomUsers AS coach
             FROM reservation r JOIN activite a ON a.Numactivite=r.Numactivite
             JOIN salle s ON s.Numsalle=a.Numsalle JOIN utilisateur coach ON coach.NumUsers=a.NumUsers
             WHERE r.NumUsers=? AND r.statut_reservation<>'Annulee' AND a.date_heure_fin < NOW()
             ORDER BY a.date_heure_debut DESC"
        );
        $q->execute([$id]);
        return $q->fetchAll();
    }

    public function dashboard(int $id): array
    {
        $profile = $this->profil($id);
        $subscription = $this->abonnement($id);
        $q = $this->pdo->prepare(
            "SELECT COUNT(*) FROM reservation r JOIN activite a ON a.Numactivite=r.Numactivite
             WHERE r.NumUsers=? AND r.statut_reservation <> 'Annulee' AND a.date_heure_debut >= NOW()"
        );
        $q->execute([$id]);
        $upcoming = (int)$q->fetchColumn();
        $q = $this->pdo->prepare(
            "SELECT COUNT(*) FROM reservation r JOIN activite a ON a.Numactivite=r.Numactivite
             WHERE r.NumUsers=? AND r.statut_reservation <> 'Annulee'
               AND a.date_heure_debut >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
               AND a.date_heure_debut < DATE_ADD(DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'), INTERVAL 1 MONTH)"
        );
        $q->execute([$id]);
        return ['profile'=>$profile, 'subscription'=>$subscription, 'upcoming'=>$upcoming,
            'sessions_month'=>(int)$q->fetchColumn()];
    }

    public function activitesAdecouvrir(): array
    {
        return $this->pdo->query(
            "SELECT a.Numactivite, a.titre, a.type_activite, a.date_heure_debut, s.nomsalle
               FROM activite a
               JOIN salle s ON s.Numsalle = a.Numsalle
              WHERE a.date_heure_debut >= NOW() AND s.etatsalle = 'Ouverte'
              ORDER BY a.date_heure_debut
              LIMIT 3"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
