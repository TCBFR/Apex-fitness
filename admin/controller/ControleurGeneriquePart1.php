<?php

trait ControleurGeneriquePart1
{
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
}
