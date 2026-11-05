<?php

trait FournisseurTransactions
{
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
}
