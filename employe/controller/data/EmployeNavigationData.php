<?php

$prenomTechnicien = trim((string) ($employeUser['prenomUsers'] ?? ''));
$titresOnglets = [
  'tableau' => 'Tableau de bord',
  'creneaux' => $accueil ? 'Planning des activités' : 'Créneaux',
  'reservations' => 'Réservations',
  'adherents' => 'Adhérents',
  'creer-ticket' => 'Créer un ticket',
  'materiel' => 'Matériel',
  'maintenance' => 'Gestion des tickets',
  'suivi-tickets' => 'Suivi des tickets',
  'faq' => 'Questions',
  'messages' => 'Messages reçus',
];
$titrePage = $maintenance ? 'Maintenance' : 'Espace employé';
$employeeNavigation = $maintenance
  ? [
      'tableau' => ['Tableau de bord', 'home'],
      'maintenance' => ['Mes tickets', 'tool'],
      'materiel' => ['Matériel', 'building'],
      'profil' => ['Mon profil', 'user'],
    ]
  : [
      'tableau' => ['Tableau de bord', 'home'],
      'creneaux' => [$accueil ? 'Planning' : 'Créneaux', 'calendar'],
      ...($accueil ? ['reservations' => ['Réservations', 'calendar']] : []),
      'adherents' => ['Adhérents', 'users'],
      'creer-ticket' => ['Créer un ticket', 'plus'],
      ...($accueil ? ['suivi-tickets' => ['Suivi des tickets', 'tool']] : []),
      ...(!$accueil ? ['materiel' => ['Matériel', 'tool']] : []),
      'faq' => ['Questions', 'help'],
      'messages' => ['Messages reçus', 'message'],
    ];
if (!$accueil) {
  unset($employeeNavigation['messages']);
}
$sidebarItems = [];
foreach ($employeeNavigation as $id => [$label, $icon]) {
  $sidebarItems[] = [
    'id' => $id,
    'label' => $label,
    'icon' => $icon,
    'href' => $id === 'profil'
      ? url('employe/profil.php')
      : url('employe/espace.php?onglet=' . $id),
  ];
}
$sidebarActive = $onglet;
$sidebarHome = url('employe/espace.php?onglet=tableau');
$sidebarBrand = null;
$sidebarLabel = $maintenance ? 'Navigation maintenance' : 'Navigation employé';
$sidebarSubtitle = $maintenance ? 'Maintenance' : 'Espace employé';
$sidebarMemberEnabled = $maintenance || $accueil;
$sidebarMemberUnlocked = a_role('ADHERENT');
$sidebarMemberAction = url('employe/espace.php');
$topbarUser = utilisateur();
$topbarTitle = $maintenance ? 'Maintenance' : 'Espace employé';
$topbarContext = 'Espace sécurisé';
$topbarClass = 'admin-topbar';
$topbarLinks = [
  ['label' => 'Mon espace', 'href' => $sidebarHome],
  ['label' => 'Déconnexion', 'href' => url('utilisateur/deconnexion.php')],
];
?>
