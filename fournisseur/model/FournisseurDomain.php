<?php

declare(strict_types=1);

require_once __DIR__ . '/../../utilisateur/model/utilisateur.php';

class Fournisseur extends Utilisateur
{
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

    public function createSupplier(PDO $pdo): int
    {
        $pdo->beginTransaction();
        try {
            $id = parent::createAccount($pdo);
            $requete = $pdo->prepare(
                'INSERT INTO fournisseur (NumUsers, nomfourn, telephonefourn, siret, mailfourn)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $requete->execute([
                $id,
                $this->nomFournisseur,
                $this->telephone,
                $this->siret,
                $this->emailFournisseur,
            ]);
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function updateSupplier(PDO $pdo, int $id): void
    {
        $pdo->beginTransaction();
        try {
            parent::updateAccount($pdo, $id);
            $requete = $pdo->prepare(
                'UPDATE fournisseur
                 SET nomfourn = ?, telephonefourn = ?, siret = ?, mailfourn = ?
                 WHERE NumUsers = ?'
            );
            $requete->execute([
                $this->nomFournisseur,
                $this->telephone,
                $this->siret,
                $this->emailFournisseur,
                $id,
            ]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public static function findSupplierById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare(
            'SELECT u.NumUsers, u.nomUsers, u.prenomUsers, u.mailUsers,
                    f.nomfourn, f.telephonefourn, f.siret, f.mailfourn
             FROM utilisateur u JOIN fournisseur f ON f.NumUsers = u.NumUsers
             WHERE u.NumUsers = ?'
        );
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findSuppliers(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT u.NumUsers, u.nomUsers, u.prenomUsers, u.mailUsers,
                    f.nomfourn, f.telephonefourn, f.siret, f.mailfourn
             FROM utilisateur u JOIN fournisseur f ON f.NumUsers = u.NumUsers
             ORDER BY f.nomfourn'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteSupplier(PDO $pdo, int $id): void
    {
        Utilisateur::deleteAccount($pdo, $id);
    }

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
