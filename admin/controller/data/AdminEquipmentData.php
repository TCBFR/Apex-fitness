<?php

$ticketsEquipementsAdmin = [];
$interventionsParTicketAdmin = [];
$commandesMaterielAdmin = [];
if ($onglet === 'equipements') {
    $ticketsEquipementsAdmin = $pdo->query(
        "SELECT d.Nummaint, d.description_problememaint, d.priorite, d.date_demandemaint,
                d.NumUsers_technicien, eq.Numequip, eq.nomequip, te.libelletypeequip,
                st.libelle_statut,
                demandeur.prenomUsers AS demandeur_prenom, demandeur.nomUsers AS demandeur_nom,
                fonction_demandeur.libellefonc AS demandeur_fonction,
                technicien.prenomUsers AS technicien_prenom, technicien.nomUsers AS technicien_nom,
                fonction_technicien.libellefonc AS technicien_fonction
           FROM demande_maintenance d
           JOIN equipement eq ON eq.Numequip = d.Numequip
           JOIN type_equipement te ON te.Numtypeequip = eq.Numtypeequip
           JOIN statut st ON st.Numstatut = d.Numstatut
           JOIN employe employe_demandeur ON employe_demandeur.NumUsers = d.NumUsers
           JOIN fonction fonction_demandeur ON fonction_demandeur.Numfonc = employe_demandeur.Numfonc
           JOIN utilisateur demandeur ON demandeur.NumUsers = d.NumUsers
           LEFT JOIN employe employe_technicien ON employe_technicien.NumUsers = d.NumUsers_technicien
           LEFT JOIN fonction fonction_technicien ON fonction_technicien.Numfonc = employe_technicien.Numfonc
           LEFT JOIN utilisateur technicien ON technicien.NumUsers = d.NumUsers_technicien
          ORDER BY CASE st.libelle_statut WHEN 'Terminee' THEN 2 WHEN 'À valider' THEN 1 ELSE 0 END,
                   d.date_demandemaint DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
    if ($ticketsEquipementsAdmin) {
        $idsTickets = array_map(static fn(array $ticket): int => (int) $ticket['Nummaint'], $ticketsEquipementsAdmin);
        $parametresTickets = implode(',', array_fill(0, count($idsTickets), '?'));
        $queryInterventionsAdmin = $pdo->prepare(
            "SELECT i.Nummaint, i.date_intervention, i.commentaire, s.libelle_statut,
                    u.prenomUsers, u.nomUsers, f.libellefonc
               FROM intervention i
               JOIN utilisateur u ON u.NumUsers = i.NumUsers
               JOIN employe e ON e.NumUsers = i.NumUsers
               JOIN fonction f ON f.Numfonc = e.Numfonc
               JOIN statut s ON s.Numstatut = i.Numstatut
              WHERE i.Nummaint IN ($parametresTickets)
              ORDER BY i.date_intervention, i.Numintervention"
        );
        $queryInterventionsAdmin->execute($idsTickets);
        foreach ($queryInterventionsAdmin->fetchAll(PDO::FETCH_ASSOC) as $interventionAdmin) {
            $interventionsParTicketAdmin[(int) $interventionAdmin['Nummaint']][] = $interventionAdmin;
        }
    }
    $commandesMaterielAdmin = $pdo->query(
        "SELECT c.Numcommande, c.Numequip, c.quantite, c.message, c.statut_commande,
                c.date_demande, c.date_decision, c.date_reception,
                eq.nomequip, eq.stock_actuel, eq.seuil_alerte, te.libelletypeequip,
                demandeur.prenomUsers AS demandeur_prenom, demandeur.nomUsers AS demandeur_nom,
                validateur.prenomUsers AS validateur_prenom, validateur.nomUsers AS validateur_nom
           FROM commande_materiel c
           JOIN equipement eq ON eq.Numequip = c.Numequip
           JOIN type_equipement te ON te.Numtypeequip = eq.Numtypeequip
           JOIN utilisateur demandeur ON demandeur.NumUsers = c.NumUsers
           LEFT JOIN utilisateur validateur ON validateur.NumUsers = c.NumUsers_admin
          ORDER BY CASE c.statut_commande
                     WHEN 'En attente' THEN 0
                     WHEN 'Approuvée' THEN 1
                     ELSE 2
                   END, c.date_demande DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
}
