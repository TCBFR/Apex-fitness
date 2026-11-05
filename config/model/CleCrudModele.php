<?php

declare(strict_types=1);

trait CleCrudModele
{
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
