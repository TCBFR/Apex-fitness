<?php

if ($action === 'repondre_faq') {
        $num     = (int) $_POST['interaction'];
        $reponse = trim($_POST['reponse'] ?? '');
      $faq->repondre($num, $reponse, $_POST['categorie'] ?? 'General');
        journalise('REPONSE_FAQ', 'NumInteraction=' . $num);
        flash('succes', $reponse !== '' ? 'Réponse publiée.' : 'Réponse enregistrée comme brouillon.');
        redirige('employe/espace.php?onglet=faq');
    }
