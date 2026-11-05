<?php

if ($action === 'repondre_avis') {
        $num = (int) ($_POST['num_interaction'] ?? 0);
        $reponse = trim((string) ($_POST['reponse'] ?? ''));

        try {
            $avisController = new AvisController();
            $avisController->repondre($num, $reponse);
            journalise('REPONSE_AVIS', 'NumInteraction=' . $num);
            flash('succes', 'Réponse enregistrée pour cet avis.');
        } catch (Throwable $ex) {
            flash('erreur', $ex->getMessage());
        }
        redirige('admin/espace.php?onglet=avis');
    }
