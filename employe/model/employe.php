<?php
require_once __DIR__ . '/../../utilisateur/model/utilisateur.php';

class Employe extends Utilisateur
{
    protected const TABLE = 'employe';

    private string $matricule;
    private string $dateEmbauche;
    private int $numFonction;

    public function __construct(
        string $nom,
        string $prenom,
        string $email,
        string $motDePasse,
        string $matricule,
        string $dateEmbauche,
        int $numFonction
    ) {
        parent::__construct($nom, $prenom, $email, $motDePasse);
        $this->matricule = $matricule;
        $this->dateEmbauche = $dateEmbauche;
        $this->numFonction = $numFonction;
    }

    public function createEmployee(PDO $pdo): int
    {
        $pdo->beginTransaction();
        try {
            $id = parent::createAccount($pdo);
            $requete = $pdo->prepare(
                'INSERT INTO employe (NumUsers, matriculeemp, Date_embaucheemp, Numfonc)
                 VALUES (?, ?, ?, ?)'
            );
            $requete->execute([$id, $this->matricule, $this->dateEmbauche, $this->numFonction]);
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function updateEmployee(PDO $pdo, int $id): void
    {
        $pdo->beginTransaction();
        try {
            parent::updateAccount($pdo, $id);
            $requete = $pdo->prepare(
                'UPDATE employe SET matriculeemp = ?, Date_embaucheemp = ?, Numfonc = ?
                 WHERE NumUsers = ?'
            );
            $requete->execute([$this->matricule, $this->dateEmbauche, $this->numFonction, $id]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public static function findEmployeeById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare(
            'SELECT u.NumUsers, u.nomUsers, u.prenomUsers, u.mailUsers,
                    e.matriculeemp, e.Date_embaucheemp, e.Numfonc
             FROM utilisateur u JOIN employe e ON e.NumUsers = u.NumUsers
             WHERE u.NumUsers = ?'
        );
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findEmployees(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT u.NumUsers, u.nomUsers, u.prenomUsers, u.mailUsers,
                    e.matriculeemp, e.Date_embaucheemp, e.Numfonc
             FROM utilisateur u JOIN employe e ON e.NumUsers = u.NumUsers
             ORDER BY u.nomUsers, u.prenomUsers'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteEmployee(PDO $pdo, int $id): void
    {
        Utilisateur::deleteAccount($pdo, $id);
    }
}