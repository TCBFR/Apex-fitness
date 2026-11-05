<?php

$utilisateurs = Utilisateur::tousLesComptes();
$adherents = $pdo->query(
    "SELECT u.NumUsers, u.nomUsers, u.prenomUsers, u.mailUsers, u.telUsers, u.Date_CreationUsers,
            a.matriculeadh, a.Date_inscriptionadh, a.Date_finadh, a.date_naissance, a.sexe,
            a.adresse_postale, a.photo_profil, a.id_commune,
            COALESCE(c.nom_commune, a.ville) AS nom_commune,
            COALESCE(c.code_postal, a.code_postal) AS code_postal,
            EXISTS (SELECT 1 FROM employe e WHERE e.NumUsers = u.NumUsers) AS est_employe,
            (SELECT CONCAT(ab.libelle_abo, ' • ', s.statut_souscription)
               FROM souscrire s JOIN abonnement ab ON ab.Numabo = s.Numabo
              WHERE s.NumUsers = u.NumUsers ORDER BY s.date_debut DESC LIMIT 1) AS abonnement
       FROM utilisateur u JOIN adherent a ON a.NumUsers = u.NumUsers
       LEFT JOIN commune c ON c.id_commune = a.id_commune
      ORDER BY u.nomUsers, u.prenomUsers"
)->fetchAll(PDO::FETCH_ASSOC);
$communes = $pdo->query('SELECT id_commune, nom_commune, code_postal FROM commune ORDER BY nom_commune')->fetchAll(PDO::FETCH_ASSOC);
$activitesCatalogueAdmin = [];
$activiteCatalogueSelectionnee = null;
$vueActiviteCatalogue = (string) ($_GET['vue'] ?? 'voir');
if ($onglet === 'activites') {
    $activitesCatalogueAdmin = ActiviteCatalogue::toutes($pdo);
    $idActiviteSelectionnee = (int) ($_GET['activite_id'] ?? 0);
    if ($idActiviteSelectionnee > 0) $activiteCatalogueSelectionnee = ActiviteCatalogue::trouver($pdo, $idActiviteSelectionnee);
}
