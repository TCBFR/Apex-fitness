<?php

require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/controller/AvisController.php';

// Traite les actions du formulaire avant de préparer la vue publique.
$controller = new AvisController();
$erreur = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    if (!connecte() || !a_role('ADHERENT') || a_role('EMPLOYE')) {
        http_response_code(403);
        $erreur = 'Seuls les adhérents non employés peuvent donner un avis.';
    } else {
        try {
            $controller->creer(
                (int) utilisateur()['NumUsers'],
                (int) ($_POST['note'] ?? 0),
                (string) ($_POST['commentaire'] ?? '')
            );
            journalise('AVIS_SALLE', 'Apex Fitness');
            flash('succes', 'Merci pour votre avis.');
            redirige('avis/public.php');
        } catch (Throwable $exception) {
            $erreur = $exception->getMessage();
        }
    }
}

$avis = $controller->index();
$moyenne = $avis ? array_sum(array_column($avis, 'noteavis')) / count($avis) : 0;
$noteFiltre = isset($_GET['note']) ? max(1, min(5, (int) $_GET['note'])) : null;
$compteurs = array_fill(1, 5, 0);
foreach ($avis as $item) {
    $compteurs[(int) $item['noteavis']]++;
}
$maxCompteur = max($compteurs) ?: 1;
$avisAffiches = $controller->filtrerParNote($avis, $noteFiltre);
$titrePage = 'Avis des adhérents';
$pageActive = 'avis';
require __DIR__ . '/view/public.php';
