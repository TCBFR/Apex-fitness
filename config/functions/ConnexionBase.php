<?php

declare(strict_types=1);

/**
 * Connexion à la base de données et fonctions utilitaires de base
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            http_response_code(500);
            exit('Connexion à la base impossible. Vérifiez config/config.php. Détail : ' . $exception->getMessage());
        }
    }
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path): string
{
    $base = APP_BASE;
    if ($base === '') {
        $publicRoot = realpath(__DIR__ . '/../public');
        $webRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
        if ($publicRoot !== false && $webRoot !== false) {
            $webRoot = rtrim($webRoot, DIRECTORY_SEPARATOR);
            $webRootPrefix = $webRoot === '' ? DIRECTORY_SEPARATOR : $webRoot . DIRECTORY_SEPARATOR;
            if ($publicRoot !== $webRoot && str_starts_with($publicRoot, $webRootPrefix)) {
                $subdirectory = substr($publicRoot, strlen($webRootPrefix));
                $base = '/' . str_replace(DIRECTORY_SEPARATOR, '/', $subdirectory);
            }
        }
    }
    $base = rtrim($base, '/');
    $urlParts = parse_url($path);
    $route = ltrim($urlParts['path'] ?? '', '/');
    $query = [];
    if (isset($urlParts['query'])) {
        parse_str($urlParts['query'], $query);
    }

    if ($route === '') {
        return $base . '/';
    }

    if (str_starts_with($route, 'assets/')) {
        $assetUrl = $base . '/' . $route;
        return $assetUrl . (isset($urlParts['query']) ? '?' . $urlParts['query'] : '');
    }

    return $base . '/index.php?' . http_build_query(['route' => $route] + $query);
}

function baseUrl(): string
{
    return rtrim(url(''), '/');
}

function redirige(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function champ_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">';
}
function verifie_csrf(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Session expirée. Rechargez la page et recommencez.');
    }
}