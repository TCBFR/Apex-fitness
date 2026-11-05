<?php

declare(strict_types=1);

require_once __DIR__ . '/../../interaction_adherent/model/interaction_adherent.php';
require_once __DIR__ . '/AvisReviewCreation.php';
require_once __DIR__ . '/AvisPersistence.php';
require_once __DIR__ . '/AvisPublication.php';

final class Avis extends InteractionAdherent
{
    protected const TABLE = 'avis';
    private int $numReservation;
    private int $note;
    private string $commentaire;
    private ?string $reponse;
    use AvisReviewCreation;
    use AvisPersistence;
    use AvisPublication;

    public function __construct(
        int $numUtilisateur = 0,
        int $numReservation = 0,
        int $note = 0,
        string $commentaire = '',
        ?PDO $pdo = null
    ) {
        parent::__construct($numUtilisateur, 'AVIS', $pdo);
        $this->numReservation = $numReservation;
        $this->note = $note;
        $this->commentaire = $commentaire;
        $this->reponse = null;
    }
}
