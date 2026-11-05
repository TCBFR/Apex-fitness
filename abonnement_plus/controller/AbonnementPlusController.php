<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/abonnement_plus.php';

/** Relie les routes CRUD des offres Plus à leur modèle métier. */
final class AbonnementPlusController extends ControleurGenerique
{
    public function __construct()
    {
        parent::__construct(AbonnementPlus::class);
    }
}
