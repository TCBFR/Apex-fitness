<?php

declare(strict_types=1);

trait UtilisateurPersistence
{
    public function createAccount(PDO $pdo): int
    {
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail invalide.');
        }
        if ($this->nom === '' || $this->prenom === '' || strlen($this->motDePasse) < 8) {
            throw new InvalidArgumentException('Nom, prénom et mot de passe valide obligatoires.');
        }

        $requete = $pdo->prepare(
            'INSERT INTO utilisateur (nomUsers, prenomUsers, mailUsers, passwordUsers)
             VALUES (?, ?, ?, ?)'
        );
        $requete->execute([
            $this->nom,
            $this->prenom,
            $this->email,
            MotDePasse::hacher($this->motDePasse),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function updateAccount(PDO $pdo, int $id): void
    {
        if ($id < 1 || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Compte ou e-mail invalide.');
        }

        $requete = $pdo->prepare(
            'UPDATE utilisateur
             SET nomUsers = ?, prenomUsers = ?, mailUsers = ?
             WHERE NumUsers = ?'
        );
        $requete->execute([$this->nom, $this->prenom, $this->email, $id]);
    }

    public static function findAccountById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare(
            'SELECT NumUsers, nomUsers, prenomUsers, mailUsers, telUsers
             FROM utilisateur
             WHERE NumUsers = ?'
        );
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findAccounts(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT NumUsers, nomUsers, prenomUsers, mailUsers, telUsers
             FROM utilisateur
             ORDER BY nomUsers, prenomUsers'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteAccount(PDO $pdo, int $id): void
    {
        $requete = $pdo->prepare('DELETE FROM utilisateur WHERE NumUsers = ?');
        $requete->execute([$id]);
    }
}
