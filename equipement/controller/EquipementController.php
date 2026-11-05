<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/equipement.php';

/** Relie les routes CRUD des équipements à leur modèle métier. */
final class EquipementController extends ControleurGenerique
{
    public function __construct()
    {
        parent::__construct(Equipement::class);
    }
}
