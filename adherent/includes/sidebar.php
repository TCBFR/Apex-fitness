<?php
$adherentNavigation = [
    ['dashboard', 'Tableau de bord', 'home', 'espace.php'],
    ['planning', 'Planning', 'calendar', 'view/planning.php'],
    ['reservations', 'Mes réservations', 'bag', 'view/reservations.php'],
    ['performance', 'Performance', 'chart', 'view/performance.php'],
    ['abonnement', 'Mon abonnement', 'card', 'view/abonnement.php'],
    ['messages', 'Messages', 'message', 'view/messagerie.php'],
    ['profil', 'Mon profil', 'user', 'view/profil.php'],
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
$sidebarHome = url('adherent/espace.php');
$sidebarLabel = 'Navigation adhérent';
$sidebarSubtitle = 'Plus fort chaque jour';

require dirname(__DIR__, 2) . '/layout/sidebar.php';
