<?php

declare(strict_types=1);

require_once __DIR__ . '/../../utilisateur/model/utilisateur.php';
require_once __DIR__ . '/FournisseurTransactions.php';
require_once __DIR__ . '/FournisseurManagement.php';

class Fournisseur extends Utilisateur
{
    use FournisseurTransactions;
    use FournisseurManagement;

    protected const TABLE = 'fournisseur';

    private string $nomFournisseur;
    private ?string $telephone;
    private ?string $siret;
    private ?string $emailFournisseur;

    public function __construct(
        string $nom,
        string $prenom,
        string $email,
        string $motDePasse,
        string $nomFournisseur,
        ?string $telephone = null,
        ?string $siret = null,
        ?string $emailFournisseur = null
    ) {
        parent::__construct($nom, $prenom, $email, $motDePasse);
        $this->nomFournisseur = $nomFournisseur;
        $this->telephone = $telephone;
        $this->siret = $siret;
        $this->emailFournisseur = $emailFournisseur;
    }
}
