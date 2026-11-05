<?php

$questionsFaq = $faq->questions();
$messages = $accueil ? $messageController->getAllThreads((int) $moi) : [];
$statutsTickets = array_count_values(array_column($maintenances, 'libelle_statut'));
$employeUser = utilisateur();
$flashMessages = flashs();
$ticketsResolusCetteSemaine = 0;
if ($maintenance) {
  $ticketsResolusCetteSemaine = (int) $pdo->query(
    "SELECT COUNT(DISTINCT i.Nummaint)
       FROM intervention i
       JOIN statut s ON s.Numstatut = i.Numstatut
      WHERE s.libelle_statut = 'Terminee'
        AND i.date_intervention >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)"
  )->fetchColumn();
}
$ticketsOuvertsMaintenance = count(array_filter(
  $maintenances,
  static fn(array $ticket): bool => $ticket['libelle_statut'] !== 'Terminee'
));
$ticketsEnAttenteMaintenance = count(array_filter(
  $maintenances,
  static fn(array $ticket): bool => in_array($ticket['libelle_statut'], ['Ouverte', 'En attente', 'En attente de piece', 'À valider'], true)
));
$ticketsResolus30Jours = 0;
$activiteMaintenanceRecente = [];
$equipementsMaintenanceAControler = [];
if ($maintenance) {
  $ticketsResolus30Jours = (int) $pdo->query(
    "SELECT COUNT(DISTINCT i.Nummaint)
       FROM intervention i
       JOIN statut s ON s.Numstatut = i.Numstatut
      WHERE s.libelle_statut = 'Terminee'
        AND i.date_intervention >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
  )->fetchColumn();
  $activiteMaintenanceRecente = $pdo->query(
    'SELECT i.Nummaint, i.date_intervention, i.commentaire, s.libelle_statut,
            u.prenomUsers, u.nomUsers
       FROM intervention i
       JOIN statut s ON s.Numstatut = i.Numstatut
       JOIN utilisateur u ON u.NumUsers = i.NumUsers
      ORDER BY i.date_intervention DESC
      LIMIT 5'
  )->fetchAll();
  $equipementsMaintenanceAControler = array_values(array_filter(
    $equipements,
    static fn(array $equipement): bool => $equipement['libelle_statut'] !== 'Disponible'
  ));
}
