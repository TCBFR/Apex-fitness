<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/freemium.php';

/** Relie les routes CRUD des offres Freemium à leur modèle métier. */
final class FreemiumController extends EntityController
{
    public function __construct()
    {
        parent::__construct(Freemium::class);
    }
}
