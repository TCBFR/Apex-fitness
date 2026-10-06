<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/reservation.php';

/** Relie les routes CRUD des réservations à leur modèle métier. */
final class ReservationController extends EntityController
{
    public function __construct()
    {
        parent::__construct(Reservation::class);
    }
}
