<?php

trait ControleurGeneriquePart3
{
    private function hasCrudMethods(): bool
    {
        foreach (['create', 'update', 'findById', 'findAll', 'delete'] as $method) {
            if (!method_exists($this->model, $method)) {
                return false;
            }
        }
        return true;
    }

    private function encodeKey(array $row): string
    {
        $key = [];
        foreach ($this->primaryKey() as $column) {
            $key[$column] = (string) $row[$column];
        }
        return rtrim(strtr(base64_encode((string) json_encode($key)), '+/', '-_'), '=');
    }

    private function columns(): array
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
}
