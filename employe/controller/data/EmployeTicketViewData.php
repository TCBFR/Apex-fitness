<?php

$ongletSuiviTickets = $maintenance ? 'maintenance' : 'suivi-tickets';
  $ticketsPourSuivi = $maintenance ? $maintenances : $ticketsEmploye;
  $statutsPourSuivi = array_count_values(array_column($ticketsPourSuivi, 'libelle_statut'));
  $ticketsEnCoursMaintenance = $statutsPourSuivi['En cours'] ?? 0;
  $ticketsEnAttenteMaintenance = count(array_filter(
    $ticketsPourSuivi,
    static fn(array $ticket): bool => in_array(
      $ticket['libelle_statut'],
      ['Ouverte', 'En attente', 'En attente de piece', 'À valider'],
      true
    )
  ));
  $ticketsResoluMaintenance = $statutsPourSuivi['Terminee'] ?? 0;
  $ticketSelectionneId = filter_var($_GET['ticket'] ?? null, FILTER_VALIDATE_INT);
  $ticketSelectionne = null;
  foreach ($ticketsPourSuivi as $ticket) {
    if ($ticketSelectionneId && (int) $ticket['Nummaint'] === $ticketSelectionneId) {
      $ticketSelectionne = $ticket;
      break;
    }
  }
  $vueEditionTicket = $maintenance && $ticketSelectionne && (($_GET['vue_ticket'] ?? '') === 'modifier');
  $categoriesTickets = array_values(array_unique(array_column($ticketsPourSuivi, 'libelletypeequip')));
  sort($categoriesTickets, SORT_NATURAL | SORT_FLAG_CASE);
  $prioritesTickets = array_values(array_unique(array_column($ticketsPourSuivi, 'priorite')));
  sort($prioritesTickets, SORT_NATURAL | SORT_FLAG_CASE);
  $interventionsTicket = [];
  if ($ticketSelectionne) {
    $sqlInterventionsTicket =
      'SELECT i.date_intervention, i.commentaire, i.Numstatut,
             s.libelle_statut, u.prenomUsers, u.nomUsers
        FROM intervention i
        JOIN statut s ON s.Numstatut = i.Numstatut
        JOIN utilisateur u ON u.NumUsers = i.NumUsers
        WHERE i.Nummaint = ?
        ORDER BY i.date_intervention ASC, i.Numintervention ASC';
    $queryInterventionsTicket = $pdo->prepare($sqlInterventionsTicket);
    $queryInterventionsTicket->execute([(int) $ticketSelectionne['Nummaint']]);
    $interventionsTicket = $queryInterventionsTicket->fetchAll(PDO::FETCH_ASSOC);
  }
