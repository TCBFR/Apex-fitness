<?php
require_once __DIR__ . '/../../utilisateur/model/utilisateur.php';

class Admin extends Utilisateur
{
    protected const TABLE = 'admin';

    private string $matricule;
    private string $niveauDroits;
    private string $dateAffectation;

    public function __construct(
        string $nom,
        string $prenom,
        string $email,
        string $motDePasse,
        string $matricule,
        string $niveauDroits,
        string $dateAffectation
    ) {
        parent::__construct($nom, $prenom, $email, $motDePasse);
        $this->matricule = $matricule;
        $this->niveauDroits = $niveauDroits;
        $this->dateAffectation = $dateAffectation;
    }

    public function createAdmin(PDO $pdo): int
    {
        $pdo->beginTransaction();
        try {
            $id = parent::createAccount($pdo);
            $requete = $pdo->prepare(
                'INSERT INTO admin (NumUsers, matriculeadmin, niveau_droits, date_affectationadmin)
                 VALUES (?, ?, ?, ?)'
            );
            $requete->execute([$id, $this->matricule, $this->niveauDroits, $this->dateAffectation]);
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function updateAdmin(PDO $pdo, int $id): void
    {
        $pdo->beginTransaction();
        try {
            parent::updateAccount($pdo, $id);
            $requete = $pdo->prepare(
                'UPDATE admin SET matriculeadmin = ?, niveau_droits = ?, date_affectationadmin = ?
                 WHERE NumUsers = ?'
            );
            $requete->execute([$this->matricule, $this->niveauDroits, $this->dateAffectation, $id]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public static function findAdminById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare(
            'SELECT u.NumUsers, u.nomUsers, u.prenomUsers, u.mailUsers,
                    a.matriculeadmin, a.niveau_droits, a.date_affectationadmin
             FROM utilisateur u JOIN admin a ON a.NumUsers = u.NumUsers
             WHERE u.NumUsers = ?'
        );
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findAdmins(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT u.NumUsers, u.nomUsers, u.prenomUsers, u.mailUsers,
                    a.matriculeadmin, a.niveau_droits, a.date_affectationadmin
             FROM utilisateur u JOIN admin a ON a.NumUsers = u.NumUsers
             ORDER BY u.nomUsers, u.prenomUsers'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteAdmin(PDO $pdo, int $id): void
    {
        Utilisateur::deleteAccount($pdo, $id);
    }
}