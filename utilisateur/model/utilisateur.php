<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/qr_code/model/qr_code.php';

class Utilisateur
{
    protected const TABLE = 'utilisateur';

    public function create(PDO $pdo, array $columns, array $data): void
    {
        $this->saveRecord($pdo, $columns, $data);
    }

    public function update(PDO $pdo, array $columns, array $data, array $key): void
    {
        $this->saveRecord($pdo, $columns, $data, $key);
    }

    public function findById(PDO $pdo, array $columns, array $key): ?array
    {
        $where = $this->keyWhere($columns, $key);
        $query = $pdo->prepare(
            'SELECT * FROM ' . $this->quote(static::TABLE) . ' WHERE ' . $where['sql']
        );
        $query->execute($where['values']);
        return $query->fetch() ?: null;
    }

    public function findAll(PDO $pdo, array $columns): array
    {
        $primary = $this->primaryKey($columns);
        $order = implode(', ', array_map(fn(string $name): string => $this->quote($name), $primary));
        return $pdo->query(
            'SELECT * FROM ' . $this->quote(static::TABLE) . ' ORDER BY ' . $order . ' LIMIT 250'
        )->fetchAll();
    }

    public function delete(PDO $pdo, array $columns, array $key): void
    {
        $where = $this->keyWhere($columns, $key);
        $pdo->prepare(
            'DELETE FROM ' . $this->quote(static::TABLE) . ' WHERE ' . $where['sql']
        )->execute($where['values']);
    }

    private function saveRecord(PDO $pdo, array $columns, array $input, ?array $key = null): void
    {
        $primary = $this->primaryKey($columns);
        $fields = [];
        $values = [];
        foreach ($columns as $column) {
            $name = $column['COLUMN_NAME'];
            $extra = strtolower($column['EXTRA']);
            if (str_contains($extra, 'auto_increment') || str_contains($extra, 'generated')) continue;
            if ($key !== null && in_array($name, $primary, true)) continue;

            $boolean = $column['DATA_TYPE'] === 'tinyint' && $column['COLUMN_TYPE'] === 'tinyint(1)';
            if ($boolean) $value = isset($input[$name]) ? 1 : 0;
            elseif (array_key_exists($name, $input)) $value = is_scalar($input[$name]) ? trim((string) $input[$name]) : '';
            else continue;

            if (stripos($name, 'password') !== false) {
                if ($value === '' && $key !== null) continue;
                if ($value === '') {
                    throw new InvalidArgumentException('Le mot de passe est obligatoire.');
                }
                if (strlen($value) < 8) {
                    throw new InvalidArgumentException('Le mot de passe doit faire 8 caractères minimum.');
                }
                $value = password_hash($value, PASSWORD_DEFAULT);
            }
            if ($value === '' && !$boolean) {
                if ($column['IS_NULLABLE'] === 'YES') $value = null;
                elseif ($column['COLUMN_DEFAULT'] !== null) continue;
                else throw new InvalidArgumentException('Le champ « ' . $name . ' » est obligatoire.');
            }
            if (is_string($value) && in_array($column['DATA_TYPE'], ['datetime', 'timestamp'], true)) {
                $value = str_replace('T', ' ', $value);
                if (strlen($value) === 16) $value .= ':00';
            }
            $fields[] = $name;
            $values[] = $value;
        }

        if ($key === null) {
            $names = array_map(fn(string $name): string => $this->quote($name), $fields);
            $sql = $fields
                ? 'INSERT INTO ' . $this->quote(static::TABLE) . ' (' . implode(', ', $names) .
                    ') VALUES (' . implode(', ', array_fill(0, count($fields), '?')) . ')'
                : 'INSERT INTO ' . $this->quote(static::TABLE) . ' () VALUES ()';
            $pdo->prepare($sql)->execute($values);
            return;
        }

        if (!$fields) throw new InvalidArgumentException('Aucune valeur modifiable n’a été fournie.');
        $where = $this->keyWhere($columns, $key);
        $set = implode(', ', array_map(fn(string $name): string => $this->quote($name) . ' = ?', $fields));
        $pdo->prepare(
            'UPDATE ' . $this->quote(static::TABLE) . ' SET ' . $set . ' WHERE ' . $where['sql']
        )->execute([...$values, ...$where['values']]);
    }

    private function primaryKey(array $columns): array
    {
        $primary = array_values(array_map(
            static fn(array $column): string => $column['COLUMN_NAME'],
            array_filter($columns, static fn(array $column): bool => $column['COLUMN_KEY'] === 'PRI')
        ));
        if (!$primary) throw new RuntimeException('Cette table ne possède pas de clé primaire.');
        return $primary;
    }

    private function keyWhere(array $columns, array $key): array
    {
        $primary = $this->primaryKey($columns);
        if (count($key) !== count($primary)) throw new InvalidArgumentException('Clé de ligne invalide.');
        $parts = [];
        $values = [];
        foreach ($primary as $column) {
            if (!array_key_exists($column, $key) || !is_scalar($key[$column])) {
                throw new InvalidArgumentException('Clé de ligne invalide.');
            }
            $parts[] = $this->quote($column) . ' = ?';
            $values[] = $key[$column];
        }
        return ['sql' => implode(' AND ', $parts), 'values' => $values];
    }

    private function quote(string $identifier): string
    {
        if (!preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/', $identifier)) {
            throw new InvalidArgumentException('Identifiant SQL invalide.');
        }
        return '`' . $identifier . '`';
    }

    private string $nom;
    private string $prenom;
    private string $email;
    private string $motDePasse;

    public function __construct(string $nom, string $prenom, string $email, string $motDePasse)
    {
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->motDePasse = $motDePasse;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): void
    {
        $this->prenom = $prenom;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getMotDePasse(): string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): void
    {
        $this->motDePasse = $motDePasse;
    }

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
            password_hash($this->motDePasse, PASSWORD_DEFAULT),
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

    public static function tousLesComptes(): array
    {
        return db()->query(
            'SELECT u.*,
                    (a.NumUsers IS NOT NULL) AS est_admin,
                    (e.NumUsers IS NOT NULL) AS est_employe,
                    (d.NumUsers IS NOT NULL) AS est_adherent,
                    f.libellefonc, e.matriculeemp, e.Date_embaucheemp
             FROM utilisateur u
             LEFT JOIN admin a ON a.NumUsers = u.NumUsers
             LEFT JOIN employe e ON e.NumUsers = u.NumUsers
             LEFT JOIN adherent d ON d.NumUsers = u.NumUsers
             LEFT JOIN fonction f ON f.Numfonc = e.Numfonc
             ORDER BY u.nomUsers'
        )->fetchAll();
    }

    public static function toutesLesFonctions(): array
    {
        return db()->query('SELECT * FROM fonction ORDER BY libellefonc')->fetchAll();
    }

    public static function creerCompte(array $data): int
    {
        self::validerCompte($data, true);
        $pdo = db();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS compte_demo (
                NumUsers INT PRIMARY KEY,
                FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );
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
            $pdo->prepare('INSERT INTO compte_demo (NumUsers) VALUES (?)')->execute([$id]);
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

    public static function inscrireAdherent(array $data): int
    {
        $nom = trim((string) ($data['nom'] ?? ''));
        $prenom = trim((string) ($data['prenom'] ?? ''));
        $email = mb_strtolower(trim((string) ($data['mail'] ?? '')));
        $dateNaissance = trim((string) ($data['date_naissance'] ?? ''));
        $ville = trim((string) ($data['ville'] ?? ''));
        $codePostal = trim((string) ($data['code_postal'] ?? ''));
        $adresse = trim((string) ($data['adresse_postale'] ?? ''));
        $password = (string) ($data['motdepasse'] ?? '');

        if ($nom === '' || $prenom === '') {
            throw new InvalidArgumentException('Le nom et le prénom sont obligatoires.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Saisissez une adresse e-mail valide.');
        }
        $dateSaisie = DateTimeImmutable::createFromFormat('!d-m-Y', $dateNaissance);
        $erreursDate = DateTimeImmutable::getLastErrors();
        if ($dateSaisie === false
            || ($erreursDate !== false && ($erreursDate['warning_count'] > 0 || $erreursDate['error_count'] > 0))
            || $dateSaisie->format('d-m-Y') !== $dateNaissance) {
            throw new InvalidArgumentException('Saisissez une date de naissance valide.');
        }
        $dateNaissance = $dateSaisie->format('Y-m-d');
        if ($ville === '' || strlen($ville) > 100) {
            throw new InvalidArgumentException('Saisissez une ville valide (100 caractères maximum).');
        }
        if (!preg_match('/^\d{5}$/', $codePostal)) {
            throw new InvalidArgumentException('Le code postal doit contenir 5 chiffres.');
        }
        if ($adresse === '' || strlen($adresse) > 255) {
            throw new InvalidArgumentException('Saisissez une adresse postale (255 caractères maximum).');
        }
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères.');
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $compte = new self($nom, $prenom, $email, $password);
            $id = $compte->createAccount($pdo);
            $pdo->prepare(
                'INSERT INTO adherent (
                    NumUsers, matriculeadh, Date_inscriptionadh, Date_finadh,
                    date_naissance, ville, code_postal, adresse_postale
                 ) VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), ?, ?, ?, ?)'
            )->execute([
                $id,
                'ADH-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT),
                $dateNaissance,
                $ville,
                $codePostal,
                $adresse,
            ]);
            self::creerQrAdherent(
                $pdo,
                $id,
                'ADH-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT)
            );
            $pdo->commit();

            return $id;
        } catch (PDOException $exception) {
            $pdo->rollBack();
            if (($exception->errorInfo[1] ?? null) === 1062) {
                throw new InvalidArgumentException('Cette adresse e-mail est déjà utilisée.');
            }
            throw $exception;
        } catch (Throwable $exception) {
            $pdo->rollBack();
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

    private static function creerRoles(PDO $pdo, int $id, array $roles, int $function): void
    {
        if (in_array('ADMIN', $roles, true)) {
            $pdo->prepare(
                'INSERT INTO admin (NumUsers, matriculeadmin, niveau_droits, date_affectationadmin)
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
            $matricule = 'ADH-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
            $pdo->prepare(
                'INSERT INTO adherent (NumUsers, matriculeadh, Date_inscriptionadh, Date_finadh)
                 VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))'
            )->execute([$id, $matricule]);
            self::creerQrAdherent($pdo, $id, $matricule);
        }
    }

    private static function creerQrAdherent(PDO $pdo, int $id, string $matricule): void
    {
        (new GenerateurQrCode())->assurerPourAdherent($pdo, $id, $matricule);
    }
}
