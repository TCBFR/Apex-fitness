<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controller/FaqPageController.php';
$controller = new FaqPageController();
$erreur = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    exige_role('ADHERENT');
    try { $controller->set((int) utilisateur()['NumUsers'], (string) ($_POST['question'] ?? '')); flash('succes', 'Question envoyée.'); redirige('faq.php'); }
    catch (Throwable $exception) { $erreur = $exception->getMessage(); }
}
$parCategorie = $controller->getAllByCategory($controller->getAll());
$titrePage = 'Questions fréquentes';
$pageActive = 'faq';
require __DIR__ . '/views/faq.php';
