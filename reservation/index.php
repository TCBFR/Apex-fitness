<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/reservation.php';
$crud = (new EntityController(Reservation::class))->traiter();
require __DIR__ . '/view/reservation.php';