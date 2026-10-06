<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/qr_code.php';

/** Relie les routes CRUD des codes QR à leur modèle métier. */
final class QrCodeController extends EntityController
{
    public function __construct()
    {
        parent::__construct(QrCode::class);
    }
}
