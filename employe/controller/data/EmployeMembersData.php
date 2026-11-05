<?php

$adherents = $pdo->query(
  "SELECT ad.*, u.nomUsers, u.prenomUsers, u.mailUsers, u.telUsers,
      CASE WHEN ad.Date_finadh IS NULL OR ad.Date_finadh >= CURDATE()
         THEN 'Actif' ELSE 'Expire' END AS statut_adherent,
      (SELECT abo.libelle_abo
       FROM souscrire su
       JOIN abonnement abo ON abo.Numabo = su.Numabo
       WHERE su.NumUsers = ad.NumUsers AND su.statut_souscription = 'En cours'
       ORDER BY su.date_debut DESC LIMIT 1) AS libelle_abo
   FROM adherent ad
   JOIN utilisateur u ON u.NumUsers = ad.NumUsers
   ORDER BY u.nomUsers, u.prenomUsers"
)->fetchAll();

$statutsAdherent = [
  ['libelle_statut' => 'Actif'],
  ['libelle_statut' => 'Expire'],
];

$planningDonnees = [];
if ($onglet === 'creneaux') {
  $planningDonnees = $planning->donneesEmploye((int) $moi);
}

$reservationsAccueil = [];
$activitesDisponiblesAccueil = [];
if ($onglet === 'reservations' && $accueil) {
  $stmtReservationsAccueil = $pdo->query(
    "SELECT r.Numres AS id_ligne, r.statut_reservation, u.NumUsers, u.prenomUsers, u.nomUsers, u.mailUsers, u.telUsers,
            ad.matriculeadh, a.Numactivite, a.titre, a.type_activite, a.date_heure_debut, a.date_heure_fin,
            a.capacite_max, s.Numsalle, s.nomsalle, coach.prenomUsers AS coach_prenom, coach.nomUsers AS coach_nom,
            (SELECT COUNT(*) FROM reservation rx WHERE rx.Numactivite=a.Numactivite AND rx.statut_reservation <> 'Annulee') AS places_prises,
            (SELECT ab.libelle_abo FROM souscrire su JOIN abonnement ab ON ab.Numabo=su.Numabo WHERE su.NumUsers=u.NumUsers AND su.statut_souscription='En cours' ORDER BY su.date_debut DESC LIMIT 1) AS libelle_abo,
            (SELECT COUNT(*) FROM reservation ra JOIN activite aa ON aa.Numactivite=ra.Numactivite WHERE ra.NumUsers=u.NumUsers AND ra.statut_reservation<>'Annulee' AND aa.date_heure_debut>=NOW()) AS reservations_a_venir
       FROM reservation r JOIN adherent ad ON ad.NumUsers=r.NumUsers JOIN utilisateur u ON u.NumUsers=ad.NumUsers
       JOIN activite a ON a.Numactivite=r.Numactivite JOIN salle s ON s.Numsalle=a.Numsalle
       JOIN utilisateur coach ON coach.NumUsers=a.NumUsers
      ORDER BY a.date_heure_debut DESC, u.nomUsers, u.prenomUsers"
  );
  $reservationsAccueil = $stmtReservationsAccueil->fetchAll(PDO::FETCH_ASSOC);
  $activitesDisponiblesAccueil = $pdo->query(
    "SELECT a.Numactivite,a.titre,a.type_activite,a.date_heure_debut,a.date_heure_fin,a.capacite_max,s.nomsalle,
            (SELECT COUNT(*) FROM reservation rx WHERE rx.Numactivite=a.Numactivite AND rx.statut_reservation<>'Annulee') AS places_prises
       FROM activite a JOIN salle s ON s.Numsalle=a.Numsalle WHERE a.date_heure_debut>=NOW() AND s.etatsalle='Ouverte' ORDER BY a.date_heure_debut LIMIT 200"
  )->fetchAll(PDO::FETCH_ASSOC);
}
