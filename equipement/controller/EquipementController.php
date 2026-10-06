<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/equipement.php';

/** Relie les routes CRUD des équipements à leur modèle métier. */
final class EquipementController extends EntityController
{
    public function __construct()
    {
        parent::__construct(Equipement::class);
    }
}
