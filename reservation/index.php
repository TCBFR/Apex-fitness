<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/ReservationController.php';
$crud = (new ReservationController())->traiter();
require __DIR__ . '/view/reservation.php';