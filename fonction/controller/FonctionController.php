<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/fonction.php';

/** Relie les routes CRUD des fonctions à leur modèle métier. */
final class FonctionController extends ControleurGenerique
{
    public function __construct()
    {
        parent::__construct(Fonction::class);
    }
}
