<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/salle.php';

/** Relie les routes CRUD des salles à leur modèle métier. */
final class SalleController extends EntityController
{
    public function __construct()
    {
        parent::__construct(Salle::class);
    }
}
