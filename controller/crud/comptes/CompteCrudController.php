<?php
final class CompteCrudController
{
    public function getAll(): array
    {
        return db()->query(
            'SELECT u.*,
                    (a.NumUsers IS NOT NULL) AS est_admin,
                    (e.NumUsers IS NOT NULL) AS est_employe,
                    (d.NumUsers IS NOT NULL) AS est_adherent,
                    f.libellefonc
             FROM utilisateur u
             LEFT JOIN administrateur a ON a.NumUsers = u.NumUsers
             LEFT JOIN employe e ON e.NumUsers = u.NumUsers
             LEFT JOIN adherent d ON d.NumUsers = u.NumUsers
             LEFT JOIN fonction f ON f.Numfonc = e.Numfonc
             ORDER BY u.nomUsers'
        )->fetchAll();
    }
    public function getFonctions(): array
    {
        return db()->query('SELECT * FROM fonction ORDER BY libellefonc')->fetchAll();
    }
    public function create(array $data): int
    {
        $this->validate($data);
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $query = $pdo->prepare(
                'INSERT INTO utilisateur (nomUsers, prenomUsers, mailUsers, telUsers, passwordUsers)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $query->execute([
                trim($data['nom']), trim($data['prenom']), trim($data['mail']),
                trim($data['tel'] ?? ''), password_hash($data['motdepasse'], PASSWORD_DEFAULT),
            ]);
            $id = (int) $pdo->lastInsertId();
            $this->createRoles($pdo, $id, $data['roles'], (int) ($data['fonction'] ?? 0));
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
    public function addAdherent(int $id): void
    {
        $status = db()->query(
            "SELECT Numstatut FROM statut WHERE type_statut='ADHERENT' AND libelle_statut='Actif'"
        )->fetchColumn();
        db()->prepare(
            'INSERT INTO adherent (NumUsers, matriculeadh, Date_inscriptionadh, Date_finadh, Numstatut)
             VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), ?)'
        )->execute([$id, 'ADH-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT), (int) $status]);
    }
    public function update(int $id, array $data): void
    {
        if ($id < 1 || trim($data['nom'] ?? '') === '' || trim($data['prenom'] ?? '') === '') {
            throw new InvalidArgumentException('Compte ou identité invalide.');
        }
        if (!filter_var($data['mail'] ?? '', FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail invalide.');
        }
        db()->prepare(
            'UPDATE utilisateur SET nomUsers = ?, prenomUsers = ?, mailUsers = ?, telUsers = ? WHERE NumUsers = ?'
        )->execute([
            trim($data['nom']), trim($data['prenom']), trim($data['mail']),
            trim($data['tel'] ?? ''), $id,
        ]);
    }
    public function delete(int $id, int $currentId): void
    {
        if ($id === $currentId) {
            throw new RuntimeException('Vous ne pouvez pas supprimer votre propre compte.');
        }
        db()->prepare('DELETE FROM utilisateur WHERE NumUsers = ?')->execute([$id]);
    }
    private function validate(array $data): void
    {
        if (trim($data['nom'] ?? '') === '' || trim($data['prenom'] ?? '') === '') {
            throw new InvalidArgumentException('Nom et prénom obligatoires.');
        }
        if (!filter_var($data['mail'] ?? '', FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail invalide.');
        }
        if (strlen($data['motdepasse'] ?? '') < 8) {
            throw new InvalidArgumentException('Le mot de passe doit faire 8 caractères minimum.');
        }
        if (empty($data['roles'])) {
            throw new InvalidArgumentException('Sélectionnez au moins un rôle.');
        }
    }

    private function createRoles(PDO $pdo, int $id, array $roles, int $function): void
    {
        if (in_array('ADMIN', $roles, true)) {
            $pdo->prepare(
                'INSERT INTO administrateur (NumUsers, matriculeadmin, niveau_droits, date_affectationadmin)
                 VALUES (?, ?, ?, CURDATE())'
            )->execute([$id, 'ADM-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT), 'TOTAL']);
        }
        if (in_array('EMPLOYE', $roles, true)) {
            $function = $function ?: (int) $pdo->query('SELECT MIN(Numfonc) FROM fonction')->fetchColumn();
            $pdo->prepare(
                'INSERT INTO employe (NumUsers, matriculeemp, Date_embaucheemp, Numfonc)
                 VALUES (?, ?, CURDATE(), ?)'
            )->execute([$id, 'EMP-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT), $function]);
        }
        if (in_array('ADHERENT', $roles, true)) {
            $status = (int) $pdo->query(
                "SELECT Numstatut FROM statut WHERE type_statut='ADHERENT' AND libelle_statut='Actif'"
            )->fetchColumn();
            $pdo->prepare(
                'INSERT INTO adherent (NumUsers, matriculeadh, Date_inscriptionadh, Date_finadh, Numstatut)
                 VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), ?)'
            )->execute([$id, 'ADH-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT), $status]);
        }
    }
}
