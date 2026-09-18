<?php

require_once __DIR__ . '/Model.php';

final class Personnel extends Model
{
    public function tous(): array
    {
        return $this->pdo->query(
            'SELECT e.NumUsers, e.matriculeemp, e.Date_embaucheemp,
                    u.nomUsers, u.prenomUsers, u.mailUsers,
                    f.Numfonc, f.libellefonc
             FROM employe e
             JOIN utilisateur u ON u.NumUsers = e.NumUsers
             JOIN fonction f ON f.Numfonc = e.Numfonc
             ORDER BY u.nomUsers, u.prenomUsers'
        )->fetchAll();
    }

    public function fonctions(): array
    {
        return $this->pdo->query('SELECT * FROM fonction ORDER BY libellefonc')->fetchAll();
    }

    public function modifierFonction(int $numUtilisateur, int $numFonction): void
    {
        $requete = $this->pdo->prepare('UPDATE employe SET Numfonc = ? WHERE NumUsers = ?');
        $requete->execute([$numFonction, $numUtilisateur]);
    }

    public function supprimer(int $numUtilisateur): void
    {
        $requete = $this->pdo->prepare('DELETE FROM employe WHERE NumUsers = ?');
        $requete->execute([$numUtilisateur]);
    }
}
