<?php
declare(strict_types=1);

/**
 * MODEL du module planning : uniquement l'acces aux donnees (SQL).
 * Les regles metier (adherent actif, creneau complet, quota Freemium,
 * conflits de salle ou de coach...) sont dans PlanningController.
 *
 * Table `activite` : une ligne = un creneau. Une recurrence hebdomadaire
 * cree une ligne par occurrence, reliees par le meme `id_serie`.
 */
final class Planning
{
    /** @var PDO */
    private $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }

    /* ------------------------------------------------------------------
       Transactions
       ------------------------------------------------------------------ */
    public function begin(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /* ------------------------------------------------------------------
       Lecture : calendrier et listes
       ------------------------------------------------------------------ */

    /** Creneaux chevauchant la plage [debut, fin[, avec places prises et "deja reserve par $numUsers". */
    public function evenements(string $debut, string $fin, int $numUsers): array
    {
        $st = $this->pdo->prepare(
            "SELECT a.Numactivite, a.titre, a.type_activite, a.date_heure_debut, a.date_heure_fin,
                    a.capacite_max, a.id_serie, s.nomsalle, u.prenomUsers,
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

    /** Derniers creneaux (tableau de gestion, cote employe). */
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

    public function titresExistants(): array
    {
        return $this->pdo->query('SELECT DISTINCT titre FROM activite ORDER BY titre')
                         ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function salles(): array
    {
        return $this->pdo->query('SELECT * FROM salle ORDER BY nomsalle')->fetchAll();
    }

    /** Employes pouvant animer un cours (tous sauf le technicien de maintenance). */
    public function coachs(): array
    {
        return $this->pdo->query(
            "SELECT e.NumUsers, u.nomUsers, u.prenomUsers
             FROM employe e
             JOIN utilisateur u ON u.NumUsers = e.NumUsers
             JOIN fonction f    ON f.Numfonc  = e.Numfonc
             WHERE f.libellefonc <> 'Technicien maintenance'
             ORDER BY u.nomUsers, u.prenomUsers"
        )->fetchAll();
    }

    /**
     * Fiche adherent : statut (Actif/Expire) + souscription en cours + offre
     * (type_abo, quota Freemium). Renvoie null si l'adherent n'existe pas.
     */
    public function ficheAdherent(int $numUsers): ?array
    {
        $st = $this->pdo->prepare(
            "SELECT ad.NumUsers, ad.matriculeadh, ad.Date_inscriptionadh, ad.Date_finadh,
                    CASE WHEN ad.Date_finadh IS NULL OR ad.Date_finadh >= CURDATE()
                         THEN 'Actif' ELSE 'Expire' END AS libelle_statut,
                    ab.Numabo, ab.libelle_abo, o.type_offre AS type_abo,
                    su.date_debut, su.date_fin, su.statut_souscription,
                    fr.nb_reservations_max_mois
             FROM adherent ad
             LEFT JOIN souscrire su ON su.NumUsers = ad.NumUsers
                                   AND su.statut_souscription = 'En cours'
                                   AND su.date_debut = (SELECT MAX(s2.date_debut) FROM souscrire s2
                                                         WHERE s2.NumUsers = ad.NumUsers
                                                           AND s2.statut_souscription = 'En cours')
             LEFT JOIN abonnement ab ON ab.Numabo   = su.Numabo
             LEFT JOIN offre o       ON o.Numoffre  = ab.Numoffre
             LEFT JOIN freemium fr   ON fr.Numoffre = o.Numoffre
             WHERE ad.NumUsers = ?
             LIMIT 1"
        );
        $st->execute([$numUsers]);
        $r = $st->fetch();
        return $r ?: null;
    }

    /* ------------------------------------------------------------------
       Reservations
       ------------------------------------------------------------------ */

    /** Creneau verrouille (FOR UPDATE) : a appeler dans une transaction. */
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

    /** Reservation existante (meme annulee) : la contrainte UNIQUE (adherent, activite) l'impose. */
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

    /** Reservations non annulees de l'adherent dans le mois de $dateCreneau. */
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

    /** Une reservation de l'adherent, avec la date de debut du cours. */
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

    /* ------------------------------------------------------------------
       Creation / suppression de creneaux
       ------------------------------------------------------------------ */

    /** Capacite de la salle, verrouillee (serialise deux employes qui planifient en meme temps). */
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

    /** Vrai si la salle OU le coach est deja pris sur [debut, fin[. */
    public function conflit(int $numSalle, int $numCoach, string $debut, string $fin): bool
    {
        $st = $this->pdo->prepare(
            'SELECT COUNT(*) FROM activite
             WHERE (Numsalle = ? OR NumUsers = ?) AND date_heure_debut < ? AND date_heure_fin > ?'
        );
        $st->execute([$numSalle, $numCoach, $fin, $debut]);
        return (int) $st->fetchColumn() > 0;
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

    /** Ligne minimale d'un creneau (id_serie) ou null s'il n'existe pas. */
    public function creneau(int $numActivite): ?array
    {
        $st = $this->pdo->prepare('SELECT Numactivite, id_serie FROM activite WHERE Numactivite = ?');
        $st->execute([$numActivite]);
        $r = $st->fetch();
        return $r ?: null;
    }

    /** Suppression d'un creneau ; les reservations partent en cascade (cle etrangere). */
    public function supprimerCreneau(int $numActivite): int
    {
        $st = $this->pdo->prepare('DELETE FROM activite WHERE Numactivite = ?');
        $st->execute([$numActivite]);
        return $st->rowCount();
    }

    /** Suppression des occurrences a venir d'une serie. */
    public function supprimerSerieAVenir(string $idSerie): int
    {
        $st = $this->pdo->prepare('DELETE FROM activite WHERE id_serie = ? AND date_heure_debut >= NOW()');
        $st->execute([$idSerie]);
        return $st->rowCount();
    }
}
