<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/demande_maintenance.php';

/** Relie les routes CRUD des demandes de maintenance à leur modèle métier. */
final class DemandeMaintenanceController extends EntityController
{
    public function __construct()
    {
        parent::__construct(DemandeMaintenance::class);
    }
}
