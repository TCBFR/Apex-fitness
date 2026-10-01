<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/reservation.php';
$crud = (new ReservationTableController())->traiter();
require __DIR__ . '/view/reservation.php';