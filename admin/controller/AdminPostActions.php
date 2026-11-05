<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    $action = $_POST['action'] ?? '';

    require __DIR__ . '/actions/AdminActionValiderCommandeMateriel.php';
    require __DIR__ . '/actions/AdminActionAction02.php';
    require __DIR__ . '/actions/AdminActionAjouterReservationAdmin.php';
    require __DIR__ . '/actions/AdminActionSupprimerSalle.php';
    require __DIR__ . '/actions/AdminActionSupprimerActiviteCatalogue.php';
    require __DIR__ . '/actions/AdminActionCreerAbonnement.php';
    require __DIR__ . '/actions/AdminActionModifierAbonnementAdmin.php';
    require __DIR__ . '/actions/AdminActionResilierAbonnement.php';
    require __DIR__ . '/actions/AdminActionCreerUtilisateur.php';
    require __DIR__ . '/actions/AdminActionCreerTechnicien.php';
    require __DIR__ . '/actions/AdminActionCreerCoach.php';
    require __DIR__ . '/actions/AdminActionCreerEmploye.php';
    require __DIR__ . '/actions/AdminActionModifierTechnicien.php';
    require __DIR__ . '/actions/AdminActionAjouterAdherent.php';
    require __DIR__ . '/actions/AdminActionModifierUtilisateur.php';
    require __DIR__ . '/actions/AdminActionModifierAdherent.php';
    require __DIR__ . '/actions/AdminActionRepondreAvis.php';
    require __DIR__ . '/actions/AdminActionModifierCoach.php';
    require __DIR__ . '/actions/AdminActionModifierEmploye.php';
    require __DIR__ . '/actions/AdminActionSupprimer.php';
}
