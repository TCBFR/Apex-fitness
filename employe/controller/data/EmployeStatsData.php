<?php

$aujourdhui = date('Y-m-d');

$stats = [
    'adherents' => (int) $pdo->query(
    "SELECT COUNT(*) FROM adherent WHERE Date_finadh IS NULL OR Date_finadh >= CURDATE()"
  )->fetchColumn(),
    'reservations' => (int) $pdo->query(
        "SELECT COUNT(*) FROM reservation r JOIN activite a ON a.Numactivite=r.Numactivite
         WHERE DATE(a.date_heure_debut)=CURDATE() AND r.statut_reservation<>'Annulee'"
    )->fetchColumn(),
    'creneaux' => (int) $pdo->query(
        'SELECT COUNT(*) FROM activite WHERE DATE(date_heure_debut)=CURDATE()'
    )->fetchColumn(),
    'dispo' => (int) $pdo->query(
        "SELECT COUNT(*) FROM equipement e JOIN statut s ON s.Numstatut=e.Numstatut WHERE s.libelle_statut='Disponible'")->fetchColumn(),
    'maint' => (int) $pdo->query(
        "SELECT COUNT(*) FROM equipement e JOIN statut s ON s.Numstatut=e.Numstatut WHERE s.libelle_statut='Maintenance'")->fetchColumn(),
    'hs' => (int) $pdo->query(
        "SELECT COUNT(*) FROM equipement e JOIN statut s ON s.Numstatut=e.Numstatut WHERE s.libelle_statut='Hors service'")->fetchColumn(),
];

$remplissage = $pdo->query(
  "SELECT a.*, a.titre AS libelleact,
      (SELECT COUNT(*) FROM reservation r
       WHERE r.Numactivite = a.Numactivite AND r.statut_reservation <> 'Annulee') AS places_prises
   FROM activite a
   WHERE DATE(a.date_heure_debut) = CURDATE()
   ORDER BY a.date_heure_debut"
)->fetchAll();
