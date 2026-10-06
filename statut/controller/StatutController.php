<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/statut.php';

/** Relie les routes CRUD des statuts à leur modèle métier. */
final class StatutController extends EntityController
{
    public function __construct()
    {
        parent::__construct(Statut::class);
    }
}
