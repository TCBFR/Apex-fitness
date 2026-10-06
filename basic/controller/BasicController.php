<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/basic.php';

/** Relie les routes CRUD des offres Basic à leur modèle métier. */
final class BasicController extends EntityController
{
    public function __construct()
    {
        parent::__construct(Basic::class);
    }
}
