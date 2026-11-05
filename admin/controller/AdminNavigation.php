<?php

$flashMessages = flashs();
$adminUser = utilisateur();
$adminNavigation = [
    'dashboard' => ['Tableau de bord', 'home'],
    'adherents' => ['Adhérents', 'user'],
    'utilisateurs' => ['Employé', 'users'],
    'abonnements' => ['Abonnements', 'card'],
    'paiements' => ['Paiements', 'card'],
    'planning' => ['Planning', 'calendar'],
    'activites' => ['Activités', 'activity'],
    'reservations' => ['Réservations', 'bag'],
    'salles' => ['Salles', 'building'],
    'equipements' => ['Équipements', 'tool'],
    'avis' => ['Avis clients', 'star'],
];
$sidebarItems = [];
foreach ($adminNavigation as $id => [$label, $icon]) {
    $sidebarItems[] = [
        'id' => $id,
        'label' => $label,
        'icon' => $icon,
        'href' => url('admin/espace.php?onglet=' . $id),
    ];
}
$sidebarActive = $onglet;
$sidebarHome = url('admin/espace.php?onglet=dashboard');
$sidebarLabel = 'Navigation administration';
$sidebarSubtitle = 'Administration';
$topbarUser = utilisateur();
$topbarTitle = 'Administration';
$topbarContext = 'Espace sécurisé';
$topbarClass = 'admin-topbar';
$topbarLinks = [
    ['label' => 'Mon espace', 'href' => url('admin/espace.php?onglet=dashboard')],
    ['label' => 'Déconnexion', 'href' => url('utilisateur/deconnexion.php')],
];
?>
