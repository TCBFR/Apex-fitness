<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/model/OperationCrudModele.php';
require_once __DIR__ . '/../../config/model/CleCrudModele.php';

final class Fonction
{
    use OperationCrudModele;
    use CleCrudModele;

    protected const TABLE = 'fonction';

}
