<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/statut_maintenance.php';

/** Relie les routes CRUD des statuts de maintenance à leur modèle métier. */
final class StatutMaintenanceController extends ControleurGenerique
{
    public function __construct()
    {
        parent::__construct(StatutMaintenance::class);
    }
}
