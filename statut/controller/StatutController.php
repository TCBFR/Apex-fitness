<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/statut.php';

/** Relie les routes CRUD des statuts à leur modèle métier. */
final class StatutController extends ControleurGenerique
{
    public function __construct()
    {
        parent::__construct(Statut::class);
    }
}
