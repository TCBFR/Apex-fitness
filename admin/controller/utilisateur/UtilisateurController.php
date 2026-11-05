<?php
/**
 * Utilisateur Controller - Routes for user functions
 * Organized MVC structure for Apex Fitness application
 */

// Core utilisateur routes
$routes = [];

$routes['utilisateur/index.php'] = '../utilisateur/index.php';
$routes['utilisateur/connexion.php'] = '../utilisateur/connexion.php';
$routes['utilisateur/inscription.php'] = '../utilisateur/inscription.php';
$routes['utilisateur/deconnexion.php'] = '../utilisateur/deconnexion.php';

return $routes;