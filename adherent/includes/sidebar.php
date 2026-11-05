<?php
$adherentNavigation = [
    ['dashboard', 'Tableau de bord', 'home', 'espace'],
    ['planning', 'Planning', 'calendar', 'planning'],
    ['reservations', 'Réservations', 'icone', 'reservations'],
    ['abonnement', 'Mon abonnement', 'card', 'abonnement'],
    ['profil', 'Mon profil', 'user', 'profil'],
    ['avis', 'Mon avis', 'star', 'avis'],
];

$sidebarItems = [];
foreach ($adherentNavigation as [$id, $label, $icon, $page]) {
    $sidebarItems[] = [
        'id' => $id,
        'label' => $label,
        'icon' => $icon,
        'href' => url('adherent/' . $page),
    ];
}

$sidebarActive = $activeNav ?? 'dashboard';
$sidebarHome = url('adherent/espace');
$sidebarLabel = 'Navigation adhérent';
$sidebarSubtitle = 'Plus fort chaque jour';

require dirname(__DIR__, 2) . '/layout/sidebar.php';