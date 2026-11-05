<?php

$ongletsAutorises = [
    'dashboard', 'utilisateurs', 'adherents', 'coachs',
    'abonnements', 'paiements', 'planning', 'activites',
    'reservations', 'salles', 'equipements', 'maintenance',
    'avis', 'messages'
];

$onglet = $_GET['onglet'] ?? 'dashboard';
if (!is_string($onglet) || !in_array($onglet, $ongletsAutorises, true)) {
    $onglet = 'dashboard';
}

$vueCompte = $_GET['compte'] ?? 'liste';
$vueMaintenance = $_GET['maintenance'] ?? 'liste';
$vuePlanning = $_GET['vue'] ?? (isset($_GET['modifier']) ? 'modifier' : 'calendrier');
if (!is_string($vuePlanning) || !in_array($vuePlanning, ['calendrier', 'ajouter', 'modifier'], true)) {
    $vuePlanning = 'calendrier';
}
$vueSalleAdmin = $_GET['vue'] ?? 'liste';
if (!is_string($vueSalleAdmin) || !in_array($vueSalleAdmin, ['liste', 'creer', 'modifier'], true)) {
    $vueSalleAdmin = 'liste';
}
