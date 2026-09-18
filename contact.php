<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controller/ContactController.php';
$controller = new ContactController();
$donnees = $controller->get();
$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    foreach (array_keys($donnees) as $champ) $donnees[$champ] = trim($_POST[$champ] ?? '');
    $erreurs = $controller->set($donnees);
    if (!$erreurs) { flash('succes', 'Message envoyé. L’accueil vous répond sous 48 heures ouvrées.'); redirige('contact.php'); }
}
$titrePage = 'Contact';
$pageActive = 'contact';
require __DIR__ . '/views/contact.php';
