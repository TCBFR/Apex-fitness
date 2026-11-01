<?php

require_once __DIR__ . '/../config/function.php';
exige_role('EMPLOYE');
if (!est_technicien_maintenance()) {
    redirige('employe/espace.php');
}

$profilEmploye = true;
require __DIR__ . '/../adherent/view/profil.php';
