<?php
/**
 * Equipement Controller - Routes for equipment functions
 * Organized MVC structure for Apex Fitness application
 */

// Core equipement routes
$routes = [];

$routes['equipement/index.php'] = '../equipement/index.php';
$routes['etat_equipement/index.php'] = '../etat_equipement/index.php';
$routes['type_equipement/index.php'] = '../type_equipement/index.php';

// Le tableau $routes est disponible mais ne retourne plus rien au niveau global
// Les contrôleurs définissent les variables et incluent les vues nécessaires