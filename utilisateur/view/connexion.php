<?php
require_once __DIR__ . '/../../config/function.php';
require_once __DIR__ . '/../controller/UtilisateurController.php';
if (connecte()) redirige(page_accueil_role());
$controller = new UtilisateurController();
$erreur = null;
$mail = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    $mail = trim($_POST['mail'] ?? '');
    $erreur = $controller->set($mail, $_POST['motdepasse'] ?? '', isset($_POST['se_souvenir']));
    if ($erreur === null) {
        flash('succes', 'Bonjour ' . utilisateur()['prenomUsers'] . ', vous êtes connecté.');
        redirige($controller->get());
    }
}
$titrePage = 'Connexion';
$pageActive = 'login';
require __DIR__ . '/login.php';