<?php

if ($maintenance && !in_array($action, $actionsTechnicien, true)) {
    http_response_code(403);
    flash('erreur', 'Votre compte est réservé à la maintenance des équipements.');
    redirige('employe/espace.php?onglet=maintenance');
  }
