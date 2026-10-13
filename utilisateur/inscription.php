<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/controller/InscriptionController.php';

if (connecte()) {
    redirige(page_accueil_role());
}

$erreur = null;
$donnees = [
    'nom' => '',
    'prenom' => '',
    'date_naissance' => '',
    'ville' => '',
    'code_postal' => '',
    'mail' => '',
    'adresse_postale' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    foreach ($donnees as $champ => $_) {
        $donnees[$champ] = trim((string) ($_POST[$champ] ?? ''));
    }

    try {
        (new InscriptionController())->inscrire($_POST);
        tente_connexion($donnees['mail'], (string) ($_POST['motdepasse'] ?? ''));
        flash('succes', 'Votre compte est créé. Bienvenue chez ATHLETIX !');
        redirige('adherent/espace.php');
    } catch (InvalidArgumentException $exception) {
        $erreur = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log('[Inscription] ' . $exception->getMessage());
        $erreur = 'La création du compte a échoué. Réessayez dans quelques instants.';
    }
}

require __DIR__ . '/view/inscription.php';
