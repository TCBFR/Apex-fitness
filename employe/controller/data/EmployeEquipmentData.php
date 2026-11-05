<?php

$equipements = $pdo->query(
  "SELECT eq.*, eq.Numequip AS num_inventaire, t.libelletypeequip,
      NULL AS nomsalle, st.libelle_statut, f.nomfourn
     FROM equipement eq
     JOIN type_equipement t ON t.Numtypeequip = eq.Numtypeequip
     JOIN statut st ON st.Numstatut = eq.Numstatut
     LEFT JOIN fournisseur f ON f.NumUsers = eq.NumUsers_fourn
   ORDER BY eq.Numequip"
)->fetchAll();

$maintenances = $pdo->query(
  "SELECT d.*, eq.Numequip AS num_inventaire, eq.nomequip, st.libelle_statut, te.libelletypeequip,
      CASE st.libelle_statut
        WHEN 'Ouverte' THEN 1
        WHEN 'En attente' THEN 2
        WHEN 'En cours' THEN 3
        WHEN 'En attente de piece' THEN 4
        WHEN 'À valider' THEN 5
        WHEN 'Terminee' THEN 6
        ELSE 0
      END AS ordre,
      u.nomUsers, u.prenomUsers, t.nomUsers AS technicien_nom, t.prenomUsers AS technicien_prenom
     FROM demande_maintenance d
     JOIN equipement eq ON eq.Numequip = d.Numequip
     JOIN type_equipement te ON te.Numtypeequip = eq.Numtypeequip
     JOIN statut st ON st.Numstatut = d.Numstatut
     JOIN statut_maintenance sm ON sm.Numstatut = d.Numstatut
     JOIN utilisateur u ON u.NumUsers = d.NumUsers
     LEFT JOIN utilisateur t ON t.NumUsers = d.NumUsers_technicien
   ORDER BY (d.NumUsers_technicien IS NULL) DESC, ordre, d.date_demandemaint DESC"
)->fetchAll();
$ticketsEmploye = array_values(array_filter(
  $maintenances,
  static fn(array $ticket): bool => (int) $ticket['NumUsers'] === (int) $moi
));
$equipementsAvecTicketActif = [];
foreach ($maintenances as $ticket) {
  if ($ticket['libelle_statut'] !== 'Terminee') {
    $equipementsAvecTicketActif[(int) $ticket['Numequip']] = true;
  }
}
$equipementsTicketables = array_values(array_filter(
  $equipements,
  static fn(array $equipement): bool => !isset($equipementsAvecTicketActif[(int) $equipement['Numequip']])
));

$statutsMaint = $pdo->query(
  "SELECT s.Numstatut, s.libelle_statut,
      CASE s.libelle_statut
        WHEN 'Ouverte' THEN 1
        WHEN 'En attente' THEN 2
        WHEN 'En cours' THEN 3
        WHEN 'En attente de piece' THEN 4
        WHEN 'À valider' THEN 5
        WHEN 'Terminee' THEN 6
        ELSE 0
      END AS ordre
   FROM statut_maintenance sm
   JOIN statut s ON s.Numstatut = sm.Numstatut
   ORDER BY ordre"
)->fetchAll();
