<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/employe.php';

/** Relie les routes CRUD des employés à leur modèle métier. */
final class EmployeController extends EntityController
{
    public function __construct()
    {
        parent::__construct(Employe::class);
    }
}
