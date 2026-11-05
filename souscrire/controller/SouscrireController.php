<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/souscrire.php';

/** Relie les routes CRUD des souscriptions à leur modèle métier. */
final class SouscrireController extends ControleurGenerique
{
    public function __construct()
    {
        parent::__construct(Souscrire::class);
    }
}
