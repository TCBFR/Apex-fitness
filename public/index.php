<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$pages = [
    'accueil/index.php' => '../accueil/index.php',
    'abonnement/index.php' => '../abonnement/index.php',
    'abonnement/catalogue.php' => '../abonnement/catalogue.php',
    'abonnement_plus/index.php' => '../abonnement_plus/index.php',
    'activite/index.php' => '../activite/index.php',
    'adherent/index.php' => '../adherent/index.php',
    'adherent/espace.php' => '../adherent/espace.php',
    'admin/index.php' => '../admin/index.php',
    'admin/espace.php' => '../admin/espace.php',
    'admin/tables.php' => '../admin/tables.php',
    'avis/index.php' => '../avis/index.php',
    'avis/public.php' => '../avis/public.php',
    'basic/index.php' => '../basic/index.php',
    'demande_maintenance/index.php' => '../demande_maintenance/index.php',
    'employe/index.php' => '../employe/index.php',
    'employe/espace.php' => '../employe/espace.php',
    'equipement/index.php' => '../equipement/index.php',
    'etat_equipement/index.php' => '../etat_equipement/index.php',
    'faq/index.php' => '../faq/index.php',
    'faq/public.php' => '../faq/public.php',
    'fonction/index.php' => '../fonction/index.php',
    'fournisseur/index.php' => '../fournisseur/index.php',
    'fournisseur/gestion.php' => '../fournisseur/gestion.php',
    'freemium/index.php' => '../freemium/index.php',
    'interaction_adherent/index.php' => '../interaction_adherent/index.php',
    'message_contact/index.php' => '../message_contact/index.php',
    'offre/index.php' => '../offre/index.php',
    'paiement/index.php' => '../paiement/index.php',
    'paiement/payer.php' => '../paiement/payer.php',
    'qr_code/index.php' => '../qr_code/index.php',
    'reservation/index.php' => '../reservation/index.php',
    'salle/index.php' => '../salle/index.php',
    'souscrire/index.php' => '../souscrire/index.php',
    'statut/index.php' => '../statut/index.php',
    'statut_maintenance/index.php' => '../statut_maintenance/index.php',
    'type_equipement/index.php' => '../type_equipement/index.php',
    'utilisateur/index.php' => '../utilisateur/index.php',
    'utilisateur/connexion.php' => '../utilisateur/connexion.php',
    'utilisateur/deconnexion.php' => '../utilisateur/deconnexion.php',
    'config/install.php' => '../config/install.php',
];

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($requestPath)) {
    http_response_code(400);
    exit('Adresse invalide.');
}

$base = baseUrl();
if ($base !== '' && str_starts_with($requestPath, $base . '/')) {
    $requestPath = substr($requestPath, strlen($base));
} elseif ($base !== '' && $requestPath === $base) {
    $requestPath = '/';
}

$requestPath = rawurldecode($requestPath);
$route = trim($requestPath, '/');
if ($route === '' || $route === 'index.php') {
    $route = 'accueil/index.php';
}

if (str_contains($route, '\\') || in_array('..', explode('/', $route), true)) {
    http_response_code(400);
    exit('Adresse invalide.');
}

if (!isset($pages[$route])) {
    http_response_code(404);
    echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Page introuvable</title>';
    echo '<link rel="stylesheet" href="' . e(url('assets/css/style.css')) . '">';
    echo '<main class="wrap"><h1>Page introuvable</h1><p><a href="' . e(url('accueil/index.php')) . '">Retour à l’accueil</a></p></main>';
    exit;
}

$page = realpath(__DIR__ . '/' . $pages[$route]);
$projectRoot = realpath(__DIR__ . '/..');
if ($page === false || $projectRoot === false ||
    !str_starts_with($page, $projectRoot . DIRECTORY_SEPARATOR) || !is_file($page)) {
    http_response_code(404);
    exit('Page introuvable.');
}

require $page;
