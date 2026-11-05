<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/type_equipement.php';

/** Relie les routes CRUD des types d’équipement à leur modèle métier. */
final class TypeEquipementController extends ControleurGenerique
{
    public function __construct()
    {
        parent::__construct(TypeEquipement::class);
    }
}
