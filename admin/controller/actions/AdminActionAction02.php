<?php

if ($onglet === 'planning' && PlanningController::estAction('admin', (string) $action)) {
        $semaineRetour = (string) ($_POST['semaine'] ?? '');
        $retourPlanning = 'admin/espace.php?onglet=planning';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $semaineRetour)) {
            $retourPlanning .= '&semaine=' . rawurlencode($semaineRetour);
        }
        $gestionPlanning->traiter('admin', 0, $retourPlanning . '#planning-calendrier');
    }
