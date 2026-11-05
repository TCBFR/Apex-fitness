<?php

declare(strict_types=1);

trait UtilisateurAccountManagement
{
    public static function creerCompte(array $data): int
    {
        self::validerCompte($data, true);
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $compte = new self(
                trim($data['nom']),
                trim($data['prenom']),
                trim($data['mail']),
                $data['motdepasse']
            );
            $id = $compte->createAccount($pdo);
            $pdo->prepare('UPDATE utilisateur SET telUsers = ? WHERE NumUsers = ?')
                ->execute([trim($data['tel'] ?? ''), $id]);
            self::creerRoles($pdo, $id, $data['roles'], (int) ($data['fonction'] ?? 0));
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function ajouterAdherent(int $id): void
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $matricule = 'ADH-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
            $pdo->prepare(
                'INSERT INTO adherent (NumUsers, matriculeadh, Date_inscriptionadh, Date_finadh)
                 VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))'
            )->execute([$id, $matricule]);
            self::creerQrAdherent($pdo, $id, $matricule);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public static function modifierCompte(int $id, array $data): void
    {
        self::validerCompte($data, false);
        $compte = new self(
            trim($data['nom']),
            trim($data['prenom']),
            trim($data['mail']),
            ''
        );
        $compte->updateAccount(db(), $id);
        db()->prepare('UPDATE utilisateur SET telUsers = ? WHERE NumUsers = ?')
            ->execute([trim($data['tel'] ?? ''), $id]);
    }

    public static function supprimerCompte(int $id, int $currentId): void
    {
        if ($id === $currentId) {
            throw new RuntimeException('Vous ne pouvez pas supprimer votre propre compte.');
        }
        self::deleteAccount(db(), $id);
    }

    private static function validerCompte(array $data, bool $creation): void
    {
        if (trim($data['nom'] ?? '') === '' || trim($data['prenom'] ?? '') === '') {
            throw new InvalidArgumentException('Nom et prénom obligatoires.');
        }
        if (!filter_var($data['mail'] ?? '', FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail invalide.');
        }
        if ($creation && strlen($data['motdepasse'] ?? '') < 8) {
            throw new InvalidArgumentException('Le mot de passe doit faire 8 caractères minimum.');
        }
        if ($creation && (!is_array($data['roles'] ?? null) || $data['roles'] === [])) {
            throw new InvalidArgumentException('Sélectionnez au moins un rôle.');
        }
    }
}
