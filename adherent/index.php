<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/AdherentController.php';
$crud = (new AdherentController())->traiter();
require __DIR__ . '/view/adherent.php';