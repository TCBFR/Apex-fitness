<?php

declare(strict_types=1);

class EntityController
{
    private const TABLES = [
        'utilisateur', 'fonction', 'employe', 'fournisseur', 'admin', 'adherent', 'qr_code',
        'offre', 'freemium', 'basic', 'abonnement_plus', 'abonnement', 'souscrire', 'paiement',
        'salle', 'activite', 'reservation', 'statut', 'etat_equipement', 'statut_maintenance',
        'type_equipement', 'equipement', 'demande_maintenance', 'interaction_adherent', 'avis', 'faq',
    ];

    private object $model;
    private PDO $pdo;
    private string $table;
    private ?array $columnCache = null;

    public function __construct(string $modelClass, ?PDO $pdo = null)
    {
        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException('La classe de l’entité n’existe pas.');
        }
        $reflection = new ReflectionClass($modelClass);
        $this->model = $reflection->newInstanceWithoutConstructor();
        $this->pdo = $pdo ?? db();
        $this->table = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $reflection->getShortName()));
        if (!in_array($this->table, self::TABLES, true)) {
            throw new InvalidArgumentException('Table non autorisée.');
        }
        if (!$this->hasCrudMethods()) {
            throw new InvalidArgumentException('La classe de l’entité ne propose pas les opérations CRUD.');
        }
    }

    public function traiter(): array
    {
        $error = null;
        $formValues = [];
        $selectedKey = null;
        $record = null;
        $action = (string) ($_GET['action'] ?? 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifie_csrf();
            $action = (string) ($_POST['action'] ?? '');
            try {
                if ($action === 'delete') {
                    $this->model->delete(
                        $this->pdo,
                        $this->columns(),
                        $this->decodeKey((string) ($_POST['key'] ?? ''))
                    );
                    flash('succes', 'Ligne supprimée.');
                } elseif ($action === 'create' || $action === 'update') {
                    $selectedKey = $action === 'update'
                        ? $this->decodeKey((string) ($_POST['key'] ?? ''))
                        : null;
                    $formValues = is_array($_POST['champs'] ?? null) ? $_POST['champs'] : [];
                    if ($selectedKey === null) {
                        $this->model->create(
                            $this->pdo,
                            $this->columns(),
                            $formValues
                        );
                    } else {
                        $this->model->update(
                            $this->pdo,
                            $this->columns(),
                            $formValues,
                            $selectedKey
                        );
                    }
                    flash('succes', $action === 'create' ? 'Ligne créée.' : 'Ligne modifiée.');
                } else {
                    throw new InvalidArgumentException('Action non reconnue.');
                }
                redirige($this->table . '/index.php');
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        if ($action === 'update' && $error !== null && $selectedKey !== null) {
            $record = $this->model->findById(
                $this->pdo,
                $this->columns(),
                $selectedKey
            );
            if ($record !== null) {
                $formValues = array_merge($record, $formValues);
                $action = 'edit';
            }
        }

        if ($action === 'edit' && $selectedKey === null) {
            try {
                $selectedKey = $this->decodeKey((string) ($_GET['key'] ?? ''));
                $record = $this->model->findById(
                    $this->pdo,
                    $this->columns(),
                    $selectedKey
                );
                if ($record === null) {
                    throw new RuntimeException('Cette ligne n’existe plus.');
                }
                $formValues = $record;
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
                $action = 'create';
            }
        } elseif ($action !== 'create') {
            $action = 'create';
        }

        $rows = $this->model->findAll($this->pdo, $this->columns());
        foreach ($rows as &$row) {
            $row['_encoded_key'] = $this->encodeKey($row);
        }
        unset($row);

        return [
            'table' => $this->table,
            'columns' => $this->columns(),
            'foreignOptions' => $this->foreignOptions(),
            'rows' => $rows,
            'action' => $action,
            'key' => $selectedKey === null ? '' : $this->encodeKey($selectedKey),
            'values' => $formValues,
            'error' => $error,
        ];
    }

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

    private function quote(string $identifier): string
    {
        if (!preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/', $identifier)) {
            throw new InvalidArgumentException('Identifiant SQL invalide.');
        }
        return '`' . $identifier . '`';
    }
}