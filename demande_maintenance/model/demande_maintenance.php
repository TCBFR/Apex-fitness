<?php

declare(strict_types=1);

final class DemandeMaintenance
{
    protected const TABLE = 'demande_maintenance';

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

}
