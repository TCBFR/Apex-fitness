<?php

declare(strict_types=1);

require_once __DIR__ . '/../../utilisateur/model/utilisateur.php';
require_once __DIR__ . '/AdherentPersistence.php';

class Adherent extends Utilisateur
{
    use AdherentPersistence;

    protected const TABLE = 'adherent';

    private string $matricule;
    private string $dateInscription;
    private ?string $dateFinAdhesion;

    public function __construct(
        string $nom,
        string $prenom,
        string $email,
        string $motDePasse,
        string $matricule,
        string $dateInscription,
        ?string $dateFinAdhesion
    ) {
        parent::__construct($nom, $prenom, $email, $motDePasse);
        $this->matricule = $matricule;
        $this->dateInscription = $dateInscription;
        $this->dateFinAdhesion = $dateFinAdhesion;
    }

    public function getMatricule(): string
    {
        return $this->matricule;
    }

    public function getDateInscription(): string
    {
        return $this->dateInscription;
    }

    public function getDateFinAdhesion(): ?string
    {
        return $this->dateFinAdhesion;
    }
}
