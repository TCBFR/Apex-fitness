<?php
// Planning de l'espace employé : réutilise les vues du planning de l'administration.
// $planningVue ('grille' ou 'formulaire'), $planningDonneesAdmin et $creneauAModifier
// sont préparés par EmployeController::espace().
$planningBase = 'employe/espace.php?onglet=creneaux';

// Dossier des vues admin (à adapter si tes fichiers planning.php / planning-form.php sont ailleurs)
$vuesPlanningAdmin = __DIR__ . '/../../../admin/view/espace/';

require $vuesPlanningAdmin . ($planningVue === 'formulaire' ? 'planning-form.php' : 'planning.php');