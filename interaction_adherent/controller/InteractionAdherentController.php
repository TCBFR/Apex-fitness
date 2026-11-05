<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/interaction_adherent.php';

/** Relie les routes CRUD des interactions adhérent à leur modèle métier. */
final class InteractionAdherentController extends ControleurGenerique
{
    public function __construct()
    {
        parent::__construct(InteractionAdherent::class);
    }
}
