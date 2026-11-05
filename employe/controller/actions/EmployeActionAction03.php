<?php

if (!$maintenance && in_array($action, $actionsTechnicien, true)) {
    http_response_code(403);
    flash('erreur', 'La maintenance est réservée au technicien maintenance.');
    redirige('employe/espace.php?onglet=tableau');
  }
