<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/etat_equipement.php';

/** Relie les routes CRUD des états d’équipement à leur modèle métier. */
final class EtatEquipementController extends ControleurGenerique
{
    public function __construct()
    {
        parent::__construct(EtatEquipement::class);
    }
}
