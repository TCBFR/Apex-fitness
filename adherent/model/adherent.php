<?php

declare(strict_types=1);

require_once __DIR__ . '/../../utilisateur/model/utilisateur.php';

class Adherent extends Utilisateur
{
    protected const TABLE = 'adherent';

    private string $matricule;
    private string $dateInscription;
    private string $dateFinAdhesion;

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

    public function createMember(PDO $pdo): int
    {
        $pdo->beginTransaction();
        try {
            $id = parent::createAccount($pdo);
            $requete = $pdo->prepare(
                'INSERT INTO adherent (NumUsers, matriculeadh, Date_inscriptionadh, Date_finadh)
                 VALUES (?, ?, ?, ?)'
            );
            $requete->execute([
                $id,
                $this->matricule,
                $this->dateInscription,
                $this->dateFinAdhesion !== '' ? $this->dateFinAdhesion : null,
            ]);
            $pdo->commit();
            return $id;
        } catch (Throwable $erreur) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $erreur;
        }
    }

    public function updateMember(PDO $pdo, int $id): void
    {
        $pdo->beginTransaction();
        try {
            parent::updateAccount($pdo, $id);
            $requete = $pdo->prepare(
                'UPDATE adherent
                 SET matriculeadh = ?, Date_inscriptionadh = ?, Date_finadh = ?
                 WHERE NumUsers = ?'
            );
            $requete->execute([
                $this->matricule,
                $this->dateInscription,
                $this->dateFinAdhesion !== '' ? $this->dateFinAdhesion : null,
                $id,
            ]);
            $pdo->commit();
        } catch (Throwable $erreur) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $erreur;
        }
    }

    public static function findMemberById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare(
            'SELECT u.NumUsers, u.nomUsers, u.prenomUsers, u.mailUsers,
                    a.matriculeadh, a.Date_inscriptionadh, a.Date_finadh
             FROM utilisateur u
             INNER JOIN adherent a ON u.NumUsers = a.NumUsers
             WHERE u.NumUsers = ?'
        );
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findMembers(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT u.NumUsers, u.nomUsers, u.prenomUsers, u.mailUsers,
                    a.matriculeadh, a.Date_inscriptionadh, a.Date_finadh
             FROM utilisateur u
             INNER JOIN adherent a ON u.NumUsers = a.NumUsers
             ORDER BY u.nomUsers, u.prenomUsers'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteMember(PDO $pdo, int $id): void
    {
        Utilisateur::deleteAccount($pdo, $id);
    }
}
