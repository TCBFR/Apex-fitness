<?php

require_once __DIR__ . '/../../admin/controller/EntityController.php';
require_once __DIR__ . '/../model/utilisateur.php';

/** Gère la connexion et les routes CRUD des comptes utilisateurs. */
final class UtilisateurController extends EntityController
{
    public function __construct()
    {
        parent::__construct(Utilisateur::class);
    }

    public function set(string $mail, string $password): ?string
    {
        if ($mail === '' || $password === '') {
            return 'Renseignez votre e-mail et votre mot de passe.';
        }
        if (tente_connexion($mail, $password)) return null;
        journalise('CONNEXION_ECHEC', $mail);
        return 'E-mail ou mot de passe incorrect.';
    }

    public function get(): string
    {
        $return = (string) ($_GET['retour'] ?? '');
        return $return !== '' ? ltrim($return, '/') : page_accueil_role();
    }

    public function traiterCrud(): array
    {
        return $this->traiter();
    }
}
