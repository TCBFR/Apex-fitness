<?php

require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/controller/MessageContactController.php';

// Prépare le formulaire et transmet son contenu au contrôleur de contact.
$controller = new ContactController();
$donnees = $controller->get();
$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    foreach (array_keys($donnees) as $champ) {
        $donnees[$champ] = trim($_POST[$champ] ?? '');
    }

    $erreurs = $controller->set($donnees);
    if (!$erreurs) {
        flash('succes', 'Message envoyé. L’accueil vous répond sous 48 heures ouvrées.');
        redirige('message_contact/index.php');
    }
}

$titrePage = 'Contact';
$pageActive = 'contact';
require __DIR__ . '/view/contact.php';
