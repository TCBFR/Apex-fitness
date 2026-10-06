<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/type_equipement.php';

/** Relie les routes CRUD des types d’équipement à leur modèle métier. */
final class TypeEquipementController extends EntityController
{
    public function __construct()
    {
        parent::__construct(TypeEquipement::class);
    }
}
