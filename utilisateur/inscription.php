<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/../commune/model/commune.php';
require_once __DIR__ . '/controller/InscriptionController.php';

if (connecte()) {
    redirige(page_accueil_role());
}

$erreur = null;
$donnees = [
    'nom' => '',
    'prenom' => '',
    'date_naissance' => '',
    'mail' => '',
    'adresse_postale' => '',
];
$commune = ['id_commune' => '', 'nom_commune' => '', 'code_postal' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    foreach ($donnees as $champ => $_) {
        $donnees[$champ] = trim((string) ($_POST[$champ] ?? ''));
    }
    // Réaffiche la commune choisie si le formulaire revient avec une erreur.
    $commune = CommuneModel::trouver((int) ($_POST['id_commune'] ?? 0)) ?? $commune;

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