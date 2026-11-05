<?php

$planningDonneesAdmin = [];
$creneauAModifier = null;
if ($onglet === 'planning') {
    $semaineDemandee = (string) ($_GET['semaine'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $semaineDemandee)) {
        $semaineDemandee = (new DateTimeImmutable('monday this week'))->format('Y-m-d');
    }
    $planningDonneesAdmin = $gestionPlanning->donneesAdmin($semaineDemandee);
    $idCreneauEdition = (int) ($_GET['modifier'] ?? 0);
    if ($vuePlanning === 'modifier' && $idCreneauEdition > 0) {
        $creneauAModifier = (new Planning())->detailsCreneau($idCreneauEdition);
    }
    if ($vuePlanning === 'modifier' && !$creneauAModifier) {
        flash('erreur', 'Le cours à modifier est introuvable.');
        $vuePlanning = 'calendrier';
    }
}
