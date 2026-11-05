<?php
declare(strict_types=1);

if (!defined('PASSWORD_ARGON2ID')) {
    throw new RuntimeException('Argon2 n\'est pas disponible dans cette version de PHP. Arrêt.');
}

class MotDePasse
{
    public static function hacher(string $motDePasse): string
    {
        return password_hash($motDePasse, PASSWORD_ARGON2ID);
    }

    public static function verifier(string $motDePasse, string $hash): bool
    {
        return password_verify($motDePasse, $hash);
    }

    public static function doitEtreRehache(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID);
    }
}
