<?php
declare(strict_types=1);

require_once __DIR__ . '/../model/utilisateur.php';

final class InscriptionController
{
    public function inscrire(array $donnees): void
    {
        $motDePasse = (string) ($donnees['motdepasse'] ?? '');
        $confirmation = (string) ($donnees['confirmation'] ?? '');

        if ($motDePasse !== $confirmation) {
            throw new InvalidArgumentException('Les mots de passe ne correspondent pas.');
        }

        Utilisateur::inscrireAdherent($donnees);
    }
}
