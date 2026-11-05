<?php

trait ControleurGeneriquePart4
{
    private function foreignOptions(): array
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

    private function primaryKey(): array
    {
        return array_values(array_map(
            static fn(array $column): string => $column['COLUMN_NAME'],
            array_filter($this->columns(), static fn(array $column): bool => $column['COLUMN_KEY'] === 'PRI')
        ));
    }

    private function decodeKey(string $encoded): array
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

    private function columnsFor(string $table): array
    {
        $query = $this->pdo->prepare(
            'SELECT COLUMN_NAME, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION'
        );
        $query->execute([$table]);
        return $query->fetchAll();
    }
}
