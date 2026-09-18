<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

abstract class Model
{
    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }
}
