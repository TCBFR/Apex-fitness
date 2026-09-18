<?php

final class LoginController
{
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
}
