<?php

if ($action === 'supprimer_faq') {
      $num = (int) $_POST['interaction'];
      $faq->supprimer($num);
      journalise('SUPPRESSION_FAQ', 'NumInteraction=' . $num);
      flash('succes', 'Question supprimée.');
      redirige('employe/espace.php?onglet=faq');
    }
