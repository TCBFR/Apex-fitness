<?php

trait FournisseurManagement
{
    public static function tous(): array
    {
        return self::findSuppliers(db());
    }

    public static function utilisateurs(): array
    {
        return db()->query(
            'SELECT NumUsers, nomUsers, prenomUsers, mailUsers
             FROM utilisateur
             WHERE NumUsers NOT IN (SELECT NumUsers FROM fournisseur)
             ORDER BY nomUsers, prenomUsers'
        )->fetchAll();
    }

    public static function enregistrer(array $donnees, ?int $id = null): void
    {
        $nom = trim((string) ($donnees['nom'] ?? ''));
        $telephone = trim((string) ($donnees['telephone'] ?? ''));
        $siret = trim((string) ($donnees['siret'] ?? ''));
        $mail = trim((string) ($donnees['mail'] ?? ''));
        if ($nom === '') {
            throw new InvalidArgumentException('Le nom du fournisseur est obligatoire.');
        }
        if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('L’e-mail du fournisseur est invalide.');
        }

        if ($id === null) {
            $userId = filter_var($donnees['utilisateur'] ?? null, FILTER_VALIDATE_INT);
            if ($userId === false || $userId < 1) {
                throw new InvalidArgumentException('Sélectionnez un utilisateur pour ce fournisseur.');
            }
            db()->prepare(
                'INSERT INTO fournisseur (NumUsers, nomfourn, telephonefourn, siret, mailfourn)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$userId, $nom, $telephone, $siret, $mail]);
            return;
        }

        db()->prepare(
            'UPDATE fournisseur
             SET nomfourn = ?, telephonefourn = ?, siret = ?, mailfourn = ?
             WHERE NumUsers = ?'
        )->execute([$nom, $telephone, $siret, $mail, $id]);
    }

    public static function supprimer(int $id): void
    {
        db()->prepare('DELETE FROM fournisseur WHERE NumUsers = ?')->execute([$id]);
    }
}
