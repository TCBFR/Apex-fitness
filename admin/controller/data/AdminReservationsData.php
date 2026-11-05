<?php

$reservationsAdmin = [];
$activitesDisponiblesReservation = [];
$adherentsReservationsAdmin = [];
$semaineReservationsAdmin = '';
if ($onglet === 'reservations') {
    $dateSemaineReservation = (string) ($_GET['semaine'] ?? '');
    $debutSemaineReservation = DateTimeImmutable::createFromFormat('!Y-m-d', $dateSemaineReservation);
    if (!$debutSemaineReservation || $debutSemaineReservation->format('Y-m-d') !== $dateSemaineReservation) {
        $debutSemaineReservation = new DateTimeImmutable('monday this week');
    } else {
        $debutSemaineReservation = $debutSemaineReservation->modify('monday this week');
    }
    $semaineReservationsAdmin = $debutSemaineReservation->format('Y-m-d');
    $finSemaineReservation = $debutSemaineReservation->modify('+7 days')->format('Y-m-d');
    $sqlReservationsBase = "SELECT %s AS id_ligne, %s AS type_ligne, %s AS date_demande,
            %s AS statut_reservation, u.NumUsers, u.prenomUsers, u.nomUsers, u.mailUsers,
            a.Numactivite, a.titre, a.type_activite, a.date_heure_debut, a.date_heure_fin,
            a.capacite_max, s.Numsalle, s.nomsalle, coach.prenomUsers AS coach_prenom,
            coach.nomUsers AS coach_nom,
            (SELECT COUNT(*) FROM reservation rx WHERE rx.Numactivite=a.Numactivite AND rx.statut_reservation <> 'Annulee') AS places_prises
          FROM %s
          JOIN adherent ad ON ad.NumUsers = %s
          JOIN utilisateur u ON u.NumUsers = ad.NumUsers
          JOIN activite a ON a.Numactivite = %s
          JOIN salle s ON s.Numsalle = a.Numsalle
          JOIN utilisateur coach ON coach.NumUsers = a.NumUsers
         WHERE a.date_heure_debut >= ? AND a.date_heure_debut < ?";
    $stmtReservationsAdmin = $pdo->prepare(sprintf($sqlReservationsBase,
        'r.Numres', "'reservation'", 'r.date_heure_reservation', 'r.statut_reservation',
        'reservation r', 'r.NumUsers', 'r.Numactivite'
    ));
    $stmtReservationsAdmin->execute([$semaineReservationsAdmin, $finSemaineReservation]);
    $reservationsAdmin = $stmtReservationsAdmin->fetchAll(PDO::FETCH_ASSOC);
    $stmtAttentesAdmin = $pdo->prepare(sprintf($sqlReservationsBase,
        'la.Numattente', "'attente'", 'la.date_inscription', "'En attente'",
        'liste_attente la', 'la.NumUsers', 'la.Numactivite'
    ));
    $stmtAttentesAdmin->execute([$semaineReservationsAdmin, $finSemaineReservation]);
    $reservationsAdmin = [...$reservationsAdmin, ...$stmtAttentesAdmin->fetchAll(PDO::FETCH_ASSOC)];
    usort($reservationsAdmin, static fn(array $a, array $b): int => strcmp($a['date_heure_debut'], $b['date_heure_debut']));
    $activitesDisponiblesReservation = $pdo->query(
        "SELECT a.Numactivite,a.titre,a.type_activite,a.date_heure_debut,a.date_heure_fin,a.capacite_max,s.nomsalle,
                (SELECT COUNT(*) FROM reservation rx WHERE rx.Numactivite=a.Numactivite AND rx.statut_reservation <> 'Annulee') AS places_prises
           FROM activite a JOIN salle s ON s.Numsalle=a.Numsalle
          WHERE a.date_heure_debut >= NOW() AND s.etatsalle='Ouverte'
          ORDER BY a.date_heure_debut LIMIT 200"
    )->fetchAll(PDO::FETCH_ASSOC);
    $adherentsReservationsAdmin = $pdo->query('SELECT u.NumUsers,u.prenomUsers,u.nomUsers,u.mailUsers FROM adherent ad JOIN utilisateur u ON u.NumUsers=ad.NumUsers ORDER BY u.nomUsers,u.prenomUsers')->fetchAll(PDO::FETCH_ASSOC);
}
