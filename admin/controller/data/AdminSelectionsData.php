<?php

$adherentSelectionne = null;
if ($onglet === 'adherents' && (int) ($_GET['id'] ?? 0) > 0) {
    foreach ($adherents as $adherent) {
        if ((int) $adherent['NumUsers'] === (int) $_GET['id']) {
            $adherentSelectionne = $adherent;
            break;
        }
    }
}
$vueAdherent = (string) ($_GET['vue'] ?? 'voir');
$techniciensMaintenance = array_values(array_filter(
    $utilisateurs,
    static fn(array $user): bool => $user['est_employe']
        && str_contains(strtolower((string) ($user['libellefonc'] ?? '')), 'maintenance')
));
$coachs = array_values(array_filter(
    $utilisateurs,
    static fn(array $user): bool => $user['est_employe']
        && in_array(
            mb_strtolower((string) ($user['libellefonc'] ?? '')),
            [...array_map('mb_strtolower', $specialisationsCoachNoms), 'coach'],
            true
        )
));
$coachSelectionne = null;
$vueCoach = (string) ($_GET['vue'] ?? 'voir');
if ($onglet === 'coachs') {
    foreach ($coachs as $coach) {
        if ((int) $coach['NumUsers'] === (int) ($_GET['id'] ?? 0)) {
            $coachSelectionne = $coach;
            break;
        }
    }
}
$employesGestion = array_values(array_filter(
    $utilisateurs,
    static function (array $user): bool {
        $fonction = mb_strtolower((string) ($user['libellefonc'] ?? ''));
        return $user['est_employe'] && (str_contains($fonction, 'maintenance') || str_contains($fonction, 'accueil'));
    }
));
$employeSelectionne = null;
if ($onglet === 'utilisateurs') {
    foreach ($employesGestion as $employeGestion) {
        if ((int) $employeGestion['NumUsers'] === (int) ($_GET['id'] ?? 0)) {
            $employeSelectionne = $employeGestion;
            break;
        }
    }
}
$compteModifie = null;
$compteConsulte = null;
$technicienSelectionne = null;
foreach ($utilisateurs as $user) {
    if ((int) $user['NumUsers'] === (int) ($_GET['id'] ?? 0)) {
        if ($vueCompte === 'modifier') {
            $compteModifie = $user;
        } elseif ($vueCompte === 'voir') {
            $compteConsulte = $user;
        }
    }
    foreach ($techniciensMaintenance as $technicien) {
        if ((int) $technicien['NumUsers'] === (int) ($_GET['id'] ?? 0)) {
            $technicienSelectionne = $technicien;
            break;
        }
    }
}
