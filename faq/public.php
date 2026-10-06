<?php

require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/controller/FaqController.php';

// Valide les questions soumises, puis regroupe les questions publiées.
$controller = new FaqController();
$controller->assurerQuestionsDemo();
$erreur = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    exige_role('ADHERENT');
    try {
        $controller->creer(
            (int) utilisateur()['NumUsers'],
            (string) ($_POST['question'] ?? '')
        );
        flash('succes', 'Question envoyée.');
        redirige('faq/public.php');
    } catch (Throwable $exception) {
        $erreur = $exception->getMessage();
    }
}

$parCategorie = $controller->grouperParCategorie($controller->index());
$titrePage = 'Questions fréquentes';
$pageActive = 'faq';
require __DIR__ . '/view/public.php';
