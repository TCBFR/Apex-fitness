<?php

declare(strict_types=1);

require_once __DIR__ . '/Model.php';

class CrudTableModel extends Model
{
    private const TABLES = [
        'utilisateur', 'fonction', 'employe', 'fournisseur', 'admin', 'adherent', 'qr_code',
        'offre', 'freemium', 'basic', 'abonnement_plus', 'abonnement', 'souscrire', 'paiement',
        'salle', 'activite', 'reservation', 'statut', 'etat_equipement', 'statut_maintenance',
        'type_equipement', 'equipement', 'demande_maintenance', 'interaction_adherent', 'avis', 'faq',
    ];

    private string $table;
    private ?array $columnCache = null;

    public function __construct(string $table, ?PDO $pdo = null)
    {
        if (!in_array($table, self::TABLES, true)) {
            throw new InvalidArgumentException('Table non autorisée.');
        }
        parent::__construct($pdo);
        $this->table = $table;
    }

    public function tableName(): string
    {
        return $this->table;
    }

    public function columns(): array
    {
        if ($this->columnCache !== null) {
            return $this->columnCache;
        }

        $query = $this->pdo->prepare(
            'SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT,
                    EXTRA, COLUMN_KEY, CHARACTER_MAXIMUM_LENGTH
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION'
        );
        $query->execute([$this->table]);
        $columns = $query->fetchAll();
        if (!$columns) {
            throw new RuntimeException('La table demandée est absente de la base de données.');
        }

        foreach ($columns as &$column) {
            $column['ENUM_OPTIONS'] = [];
            if (str_starts_with(strtolower($column['COLUMN_TYPE']), 'enum(')) {
                preg_match_all("/'([^']*)'/", $column['COLUMN_TYPE'], $matches);
                $column['ENUM_OPTIONS'] = $matches[1] ?? [];
            }
        }
        unset($column);
        return $this->columnCache = $columns;
    }

    public function primaryKey(): array
    {
        return array_values(array_map(
            static fn(array $column): string => $column['COLUMN_NAME'],
            array_filter($this->columns(), static fn(array $column): bool => $column['COLUMN_KEY'] === 'PRI')
        ));
    }

    public function rows(): array
    {
        $key = $this->primaryKey();
        if (!$key) {
            throw new RuntimeException('Cette table ne possède pas de clé primaire.');
        }
        $order = implode(', ', array_map(fn(string $name): string => $this->quote($name), $key));
        return $this->pdo->query('SELECT * FROM ' . $this->quote($this->table) . ' ORDER BY ' . $order . ' LIMIT 250')
            ->fetchAll();
    }

    public function find(array $key): ?array
    {
        $where = $this->keyWhere($key);
        $query = $this->pdo->prepare('SELECT * FROM ' . $this->quote($this->table) . ' WHERE ' . $where['sql']);
        $query->execute($where['values']);
        return $query->fetch() ?: null;
    }

    public function foreignOptions(): array
    {
        $query = $this->pdo->prepare(
            'SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL'
        );
        $query->execute([$this->table]);
        $foreignKeys = [];

        foreach ($query->fetchAll() as $foreignKey) {
            $target = $foreignKey['REFERENCED_TABLE_NAME'];
            if (!in_array($target, self::TABLES, true)) {
                continue;
            }
            $targetColumns = $this->columnsFor($target);
            $targetPrimary = array_values(array_map(
                static fn(array $column): string => $column['COLUMN_NAME'],
                array_filter($targetColumns, static fn(array $column): bool => $column['COLUMN_KEY'] === 'PRI')
            ));
            if (count($targetPrimary) !== 1) {
                continue;
            }

            $labelColumn = null;
            foreach ($targetColumns as $column) {
                if ($column['COLUMN_NAME'] === $foreignKey['REFERENCED_COLUMN_NAME']) {
                    continue;
                }
                if (preg_match('/^(libelle|nom|prenom|titre|mail|code|question)/i', $column['COLUMN_NAME'])) {
                    $labelColumn = $column['COLUMN_NAME'];
                    break;
                }
                $labelColumn ??= $column['COLUMN_NAME'];
            }
            $idColumn = $foreignKey['REFERENCED_COLUMN_NAME'];
            $select = $this->quote($idColumn);
            if ($labelColumn !== null && $labelColumn !== $idColumn) {
                $select .= ', ' . $this->quote($labelColumn);
            }
            $sql = 'SELECT ' . $select . ' FROM ' . $this->quote($target) .
                ' ORDER BY ' . $this->quote($labelColumn ?? $idColumn) . ' LIMIT 500';
            $options = [];
            foreach ($this->pdo->query($sql)->fetchAll() as $row) {
                $label = $labelColumn !== null && $labelColumn !== $idColumn
                    ? (string) $row[$labelColumn]
                    : (string) $row[$idColumn];
                $options[] = ['value' => (string) $row[$idColumn], 'label' => $label];
            }
            $foreignKeys[$foreignKey['COLUMN_NAME']] = ['options' => $options];
        }

        return $foreignKeys;
    }

    public function encodeKey(array $row): string
    {
        $key = [];
        foreach ($this->primaryKey() as $column) {
            $key[$column] = (string) $row[$column];
        }
        return rtrim(strtr(base64_encode((string) json_encode($key)), '+/', '-_'), '=');
    }

    public function decodeKey(string $encoded): array
    {
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        $key = $decoded === false ? null : json_decode($decoded, true);
        $primary = $this->primaryKey();
        if (!is_array($key) || count($key) !== count($primary)) {
            throw new InvalidArgumentException('Clé de ligne invalide.');
        }
        foreach ($primary as $column) {
            if (!array_key_exists($column, $key) || !is_scalar($key[$column])) {
                throw new InvalidArgumentException('Clé de ligne invalide.');
            }
        }
        return $key;
    }

    public function save(array $input, ?array $key = null): void
    {
        $primary = $this->primaryKey();
        $fields = [];
        $values = [];
        foreach ($this->columns() as $column) {
            $name = $column['COLUMN_NAME'];
            $extra = strtolower($column['EXTRA']);
            if (str_contains($extra, 'auto_increment') || str_contains($extra, 'generated')) {
                continue;
            }
            if ($key !== null && in_array($name, $primary, true)) {
                continue;
            }

            $isBoolean = $column['DATA_TYPE'] === 'tinyint' && $column['COLUMN_TYPE'] === 'tinyint(1)';
            if ($isBoolean) {
                $value = isset($input[$name]) ? 1 : 0;
            } elseif (!array_key_exists($name, $input)) {
                continue;
            } else {
                $value = is_scalar($input[$name]) ? trim((string) $input[$name]) : '';
            }

            if (stripos($name, 'password') !== false) {
                if ($value === '' && $key !== null) {
                    continue;
                }
                if ($value === '') {
                    throw new InvalidArgumentException('Le mot de passe est obligatoire.');
                }
                if (strlen($value) < 8) {
                    throw new InvalidArgumentException('Le mot de passe doit faire 8 caractères minimum.');
                }
                $value = password_hash($value, PASSWORD_DEFAULT);
            }

            if ($value === '' && !$isBoolean) {
                if ($column['IS_NULLABLE'] === 'YES') {
                    $value = null;
                } elseif ($column['COLUMN_DEFAULT'] !== null) {
                    continue;
                } else {
                    throw new InvalidArgumentException('Le champ « ' . $name . ' » est obligatoire.');
                }
            }

            if (is_string($value) && in_array($column['DATA_TYPE'], ['datetime', 'timestamp'], true)) {
                $value = str_replace('T', ' ', $value);
                if (strlen($value) === 16) {
                    $value .= ':00';
                }
            }
            $fields[] = $name;
            $values[] = $value;
        }

        if ($key === null) {
            $quotedFields = array_map(fn(string $name): string => $this->quote($name), $fields);
            $sql = $fields
                ? 'INSERT INTO ' . $this->quote($this->table) . ' (' . implode(', ', $quotedFields) .
                    ') VALUES (' . implode(', ', array_fill(0, count($fields), '?')) . ')'
                : 'INSERT INTO ' . $this->quote($this->table) . ' () VALUES ()';
            $this->pdo->prepare($sql)->execute($values);
            return;
        }

        if (!$fields) {
            throw new InvalidArgumentException('Aucune valeur modifiable n’a été fournie.');
        }
        $where = $this->keyWhere($key);
        $set = implode(', ', array_map(fn(string $name): string => $this->quote($name) . ' = ?', $fields));
        $this->pdo->prepare('UPDATE ' . $this->quote($this->table) . ' SET ' . $set . ' WHERE ' . $where['sql'])
            ->execute([...$values, ...$where['values']]);
    }

    public function delete(array $key): void
    {
        $where = $this->keyWhere($key);
        $this->pdo->prepare('DELETE FROM ' . $this->quote($this->table) . ' WHERE ' . $where['sql'])
            ->execute($where['values']);
    }

    private function columnsFor(string $table): array
    {
        $query = $this->pdo->prepare(
            'SELECT COLUMN_NAME, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION'
        );
        $query->execute([$table]);
        return $query->fetchAll();
    }

    private function keyWhere(array $key): array
    {
        $primary = $this->primaryKey();
        if (count($key) !== count($primary)) {
            throw new InvalidArgumentException('Clé de ligne invalide.');
        }
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
}