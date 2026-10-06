<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/activite.php';

/** Relie les routes CRUD des activités à leur modèle métier. */
final class ActiviteController extends EntityController
{
    public function __construct()
    {
        parent::__construct(Activite::class);
    }
}
