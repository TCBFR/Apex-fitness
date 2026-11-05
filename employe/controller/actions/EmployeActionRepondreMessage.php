<?php

if (!$accueil && in_array($action, ['repondre_message', 'supprimer_message'], true)) {
    http_response_code(403);
    flash('erreur', 'La messagerie des adhérents est réservée à l’employé d’accueil.');
    redirige('employe/espace.php?onglet=tableau');
  }
