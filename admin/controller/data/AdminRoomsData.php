<?php

$sallesAdmin = [];
$salleSelectionneeAdmin = null;
if ($onglet === 'salles') {
    $sallesAdmin = $pdo->query('SELECT Numsalle,nomsalle,capacitesalle,localisationsalle,superficiesalle,etatsalle,description_salle,image_salle FROM salle ORDER BY nomsalle')->fetchAll(PDO::FETCH_ASSOC);
    $idSalleSelectionnee = (int) ($_GET['id'] ?? 0);
    if ($idSalleSelectionnee > 0) {
        foreach ($sallesAdmin as $salleAdmin) {
            if ((int) $salleAdmin['Numsalle'] === $idSalleSelectionnee) { $salleSelectionneeAdmin = $salleAdmin; break; }
        }
    }
    if ($vueSalleAdmin === 'modifier' && !$salleSelectionneeAdmin) {
        flash('erreur', 'La salle à modifier est introuvable.');
        $vueSalleAdmin = 'liste';
    }
}
