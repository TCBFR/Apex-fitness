<?php

declare(strict_types=1);

trait PlanningCalendarQueries
{
    public function evenements(string $debut, string $fin, int $numUsers): array
    {
        $st = $this->pdo->prepare(
            "SELECT a.Numactivite, a.titre, a.type_activite, a.date_heure_debut, a.date_heure_fin,
                    a.capacite_max, a.id_serie, a.Numsalle, a.NumUsers, s.nomsalle, u.prenomUsers, u.nomUsers,
                    (SELECT COUNT(*) FROM reservation r
                      WHERE r.Numactivite = a.Numactivite AND r.statut_reservation <> 'Annulee') AS places_prises,
                    (SELECT COUNT(*) FROM reservation r2
                      WHERE r2.Numactivite = a.Numactivite AND r2.NumUsers = ?
                        AND r2.statut_reservation <> 'Annulee') AS reserve,
                    (SELECT COUNT(*) FROM liste_attente la
                      WHERE la.Numactivite = a.Numactivite AND la.NumUsers = ?) AS en_attente
             FROM activite a
             JOIN salle s       ON s.Numsalle = a.Numsalle
             JOIN utilisateur u ON u.NumUsers = a.NumUsers
             WHERE a.date_heure_debut < ? AND a.date_heure_fin > ?
             ORDER BY a.date_heure_debut"
        );
        $st->execute([$numUsers, $numUsers, $fin, $debut]);
        return $st->fetchAll();
    }

    public function derniersCreneaux(int $limite = 40): array
    {
        $limite = max(1, $limite);
        return $this->pdo->query(
            "SELECT a.Numactivite, a.titre, a.type_activite, a.date_heure_debut, a.date_heure_fin,
                    a.capacite_max, a.id_serie, s.nomsalle,
                    (SELECT COUNT(*) FROM reservation r
                      WHERE r.Numactivite = a.Numactivite AND r.statut_reservation <> 'Annulee') AS places_prises
             FROM activite a
             JOIN salle s ON s.Numsalle = a.Numsalle
             ORDER BY a.date_heure_debut DESC
             LIMIT $limite"
        )->fetchAll();
    }

    public function creneauxSurPeriode(string $debut, string $fin): array
    {
        $st = $this->pdo->prepare(
            "SELECT a.Numactivite, a.titre, a.type_activite, a.description, a.date_heure_debut, a.date_heure_fin,
                    a.capacite_max, a.id_serie, a.Numsalle, a.NumUsers,
                    s.nomsalle, u.prenomUsers, u.nomUsers,
                    (SELECT COUNT(*) FROM reservation r
                      WHERE r.Numactivite = a.Numactivite AND r.statut_reservation <> 'Annulee') AS places_prises
             FROM activite a
             JOIN salle s ON s.Numsalle = a.Numsalle
             JOIN utilisateur u ON u.NumUsers = a.NumUsers
             WHERE a.date_heure_debut < ? AND a.date_heure_fin > ?
             ORDER BY a.date_heure_debut"
        );
        $st->execute([$fin, $debut]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function titresExistants(): array
    {
        return $this->pdo->query('SELECT DISTINCT titre FROM activite ORDER BY titre')
                         ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function salles(): array
    {
        return $this->pdo->query("SELECT * FROM salle WHERE etatsalle = 'Ouverte' ORDER BY nomsalle")->fetchAll();
    }

    public function toutesLesSalles(): array
    {
        return $this->pdo->query('SELECT * FROM salle ORDER BY nomsalle')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function coachs(): array
    {
        return $this->pdo->query(
            "SELECT e.NumUsers, u.nomUsers, u.prenomUsers
             FROM employe e
             JOIN utilisateur u ON u.NumUsers = e.NumUsers
             JOIN fonction f    ON f.Numfonc  = e.Numfonc
             WHERE f.libellefonc NOT IN ('Technicien maintenance', 'Accueil')
             ORDER BY u.nomUsers, u.prenomUsers"
        )->fetchAll();
    }
}
