<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/adherent.php';
$crud = (new EntityController(Adherent::class))->traiter();
require __DIR__ . '/view/adherent.php';