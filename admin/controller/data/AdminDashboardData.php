<?php

$statsRoles = [
    'total'     => count($utilisateurs),
    'admins'    => count(array_filter($utilisateurs, fn($u) => $u['est_admin'])),
    'employes'  => count(array_filter($utilisateurs, fn($u) => $u['est_employe'])),
    'adherents' => count(array_filter($utilisateurs, fn($u) => $u['est_adherent'])),
    'cumuls'    => count(array_filter($utilisateurs, fn($u) => $u['est_employe'] && $u['est_adherent'])),
];
$stats = $statsRoles;

$parActivite = $pdo->query(
  "SELECT a.titre AS libelleact, COUNT(r.Numres) AS n
     FROM activite a
   LEFT JOIN reservation r ON r.Numactivite = a.Numactivite AND r.statut_reservation <> 'Annulee'
   GROUP BY a.Numactivite, a.titre ORDER BY n DESC")->fetchAll();

$parAbonnement = $pdo->query(
  "SELECT ab.libelle_abo, o.type_offre AS type_abo, COUNT(s.NumUsers) AS n
     FROM abonnement ab
   JOIN offre o ON o.Numoffre = ab.Numoffre
     LEFT JOIN souscrire s ON s.Numabo = ab.Numabo AND s.statut_souscription = 'En cours'
   GROUP BY ab.Numabo, ab.libelle_abo, o.type_offre ORDER BY n DESC")->fetchAll();

$recettes = $pdo->query(
    "SELECT DATE_FORMAT(date_paiement,'%Y-%m') AS mois, SUM(montant) AS total, COUNT(*) AS n
     FROM paiement GROUP BY mois ORDER BY mois DESC LIMIT 6")->fetchAll();
