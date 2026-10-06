<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/../admin/controller/EntityController.php';
require_once __DIR__ . '/model/interaction_adherent.php';
$crud = (new EntityController(InteractionAdherent::class))->traiter();
require __DIR__ . '/view/interaction_adherent.php';