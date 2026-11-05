<?php
/**
 * Adherent Controller - Routes for adherent/adherent member functions
 * Organized MVC structure for Apex Fitness application
 */

// Core adherent routes
$routes = [];

$routes['adherent/index.php'] = '../adherent/index.php';
$routes['adherent/espace.php'] = '../adherent/espace.php';
$routes['adherent/view/messagerie.php'] = '../adherent/view/messagerie.php';
$routes['adherent/view/planning.php'] = '../adherent/view/planning.php';
$routes['adherent/view/reservations.php'] = '../adherent/view/reservations.php';
$routes['adherent/view/abonnement.php'] = '../adherent/view/abonnement.php';
$routes['adherent/view/offres.php'] = '../adherent/view/offres.php';
$routes['adherent/view/profil.php'] = '../adherent/view/profil.php';
$routes['adherent/view/avis.php'] = '../adherent/view/avis.php';
$routes['adherent/api.php'] = '../adherent/api.php';
$routes['adherent/facture.php'] = '../adherent/facture.php';
$routes['adherent/notification_api.php'] = '../adherent/notification_api.php';
$routes['adherent/deconnexion.php'] = '../utilisateur/deconnexion.php';

// Le tableau $routes est disponible mais ne retourne plus rien au niveau global
// Les contrôleurs définissent les variables et incluent les vues nécessaires