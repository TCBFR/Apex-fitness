<?php

if (!$maintenance && PlanningController::estAction('employe', $action)) {
        $planning->traiter('employe', (int) $moi, 'employe/espace.php?onglet=creneaux');
    }
