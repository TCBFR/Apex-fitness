<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/qr_code/model/qr_code.php';
require_once __DIR__ . '/../../config/model/OperationCrudModele.php';
require_once __DIR__ . '/../../config/model/CleCrudModele.php';
require_once __DIR__ . '/UtilisateurIdentity.php';
require_once __DIR__ . '/UtilisateurPersistence.php';
require_once __DIR__ . '/UtilisateurAccountQueries.php';
require_once __DIR__ . '/UtilisateurRegistration.php';
require_once __DIR__ . '/UtilisateurAccountSchema.php';
require_once __DIR__ . '/UtilisateurAccountManagement.php';
require_once __DIR__ . '/UtilisateurAccountRoles.php';

class Utilisateur
{
    use OperationCrudModele;
    use CleCrudModele;
    use UtilisateurIdentity;
    use UtilisateurPersistence;
    use UtilisateurAccountQueries;
    use UtilisateurRegistration;
    use UtilisateurAccountSchema;
    use UtilisateurAccountManagement;
    use UtilisateurAccountRoles;

    protected const TABLE = 'utilisateur';
}
