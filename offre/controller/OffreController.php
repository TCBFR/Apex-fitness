<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/offre.php';

/** Relie les routes CRUD des offres à leur modèle métier. */
final class OffreController extends ControleurGenerique
{
    public function __construct()
    {
        parent::__construct(Offre::class);
    }
}
