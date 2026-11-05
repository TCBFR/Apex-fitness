<?php

if ($maintenance && $action === 'fermer_ticket') {
    http_response_code(403);
    flash('erreur', 'Seul l’employé qui a créé le ticket peut le fermer après validation.');
    redirige('employe/espace.php?onglet=maintenance');
  }
