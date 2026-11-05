<?php

require_once __DIR__ . '/../../config/function.php';
require_once __DIR__ . '/../../config/model/OperationCrudModele.php';
require_once __DIR__ . '/../../config/model/CleCrudModele.php';
require_once __DIR__ . '/../../abonnement/model/ContratAbonnement.php';
require_once __DIR__ . '/../../qr_code/model/qr_code.php';
require_once __DIR__ . '/PaiementOfferQueries.php';
require_once __DIR__ . '/PaiementProcessing.php';
require_once __DIR__ . '/PaiementSubscription.php';

final class Paiement
{
    use OperationCrudModele;
    use CleCrudModele;
    use PaiementOfferQueries;
    use PaiementProcessing;
    use PaiementSubscription;

    private PDO $pdo;
    protected const TABLE = 'paiement';

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }
}
