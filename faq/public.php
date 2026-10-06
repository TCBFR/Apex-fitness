<?php
require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/controller/FaqPageController.php';
$controller = new FaqPageController();
$erreur = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    exige_role('ADHERENT');
    try { $controller->set((int) utilisateur()['NumUsers'], (string) ($_POST['question'] ?? '')); flash('succes', 'Question envoyée.'); redirige('faq/public.php'); }
    catch (Throwable $exception) { $erreur = $exception->getMessage(); }
}
$parCategorie = $controller->getAllByCategory($controller->getAll());
$titrePage = 'Questions fréquentes';
$pageActive = 'faq';
require __DIR__ . '/view/public.php';
