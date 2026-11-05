<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/function.php';
require_once __DIR__ . '/AdherentEspaceProfileRead.php';
require_once __DIR__ . '/AdherentEspaceSubscription.php';
require_once __DIR__ . '/AdherentEspaceReservations.php';
require_once __DIR__ . '/AdherentEspacePayments.php';
require_once __DIR__ . '/AdherentEspaceProfileUpdate.php';
require_once __DIR__ . '/AdherentEspaceProfilePhoto.php';
require_once __DIR__ . '/AdherentEspaceMessaging.php';

/** Données et actions propres à l'espace adhérent. */
final class AdherentEspace
{
    use AdherentEspaceProfileRead,
        AdherentEspaceSubscription,
        AdherentEspaceReservations,
        AdherentEspacePayments,
        AdherentEspaceProfileUpdate,
        AdherentEspaceProfilePhoto,
        AdherentEspaceMessaging;

    public function __construct(private ?PDO $pdo = null)
    {
        $this->pdo ??= db();
    }
}
