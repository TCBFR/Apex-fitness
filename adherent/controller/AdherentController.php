<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/adherent.php';

/** Relie les routes CRUD des adhérents à leur modèle métier. */
final class AdherentController extends EntityController
{
    public function __construct()
    {
        parent::__construct(Adherent::class);
    }
}
