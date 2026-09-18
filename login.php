<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controller/LoginController.php';
if (connecte()) redirige(page_accueil_role());
$controller = new LoginController();
$erreur = null;
$mail = '';
$comptesDemo = [['admin@apex.fr', 'administratrice'], ['camille.roux@apex.fr', 'employée et adhérente'], ['marc.anselme@apex.fr', 'employé maintenance'], ['s.nabil@mail.fr', 'adhérent actif'], ['awa.diallo@mail.fr', 'adhérente expirée']];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    $mail = trim($_POST['mail'] ?? '');
    $erreur = $controller->set($mail, $_POST['motdepasse'] ?? '');
    if ($erreur === null) { flash('succes', 'Bonjour ' . utilisateur()['prenomUsers'] . ', vous êtes connecté.'); redirige($controller->get()); }
}
$titrePage = 'Connexion';
$pageActive = 'login';
require __DIR__ . '/views/login.php';
