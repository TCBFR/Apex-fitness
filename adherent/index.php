<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/adherent.php';
$crud = (new AdherentTableController())->traiter();
require __DIR__ . '/view/adherent.php';