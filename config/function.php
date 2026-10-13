<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

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

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashs(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function journalise(string $action, ?string $target = null): void
{
    try {
        db()->prepare(
            'INSERT INTO journal (NumUsers, action, cible, ip) VALUES (?, ?, ?, ?)'
        )->execute([
            $_SESSION['user']['NumUsers'] ?? null,
            $action,
            $target,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (PDOException $exception) {
        error_log('[Apex Fitness] activity log write failed: ' . $exception->getMessage());
    }
}

function euros(float|string|null $amount): string
{
    return number_format((float) $amount, 2, ',', ' ') . ' &euro;';
}

function dateFr(?string $date, bool $withTime = false): string
{
    if (!$date) {
        return '&mdash;';
    }
    return date($withTime ? 'd/m/Y \a\ H\hi' : 'd/m/Y', strtotime($date));
}

function jourHeure(string $start, string $end): string
{
    $days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
    $months = ['', 'jan.', 'fev.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'aout', 'sept.', 'oct.', 'nov.', 'dec.'];
    $time = strtotime($start);
    return sprintf(
        '%s %d %s &middot; %s &ndash; %s',
        $days[(int) date('w', $time)],
        (int) date('j', $time),
        $months[(int) date('n', $time)],
        date('H\hi', $time),
        date('H\hi', strtotime($end))
    );
}

function tente_connexion(string $mail, string $motDePasse, bool $seSouvenir = false): bool
{
    $query = db()->prepare(
        'SELECT NumUsers, nomUsers, prenomUsers, mailUsers, passwordUsers
         FROM utilisateur WHERE mailUsers = ?'
    );
    $query->execute([$mail]);
    $user = $query->fetch();

    if (!$user || !password_verify($motDePasse, $user['passwordUsers'])) {
        return false;
    }

    session_regenerate_id(true);
    if ($seSouvenir) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires' => time() + 30 * 24 * 60 * 60,
            'path' => $cookie['path'] ?: '/',
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => true,
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ]);
    }
    $_SESSION['user'] = [
        'NumUsers' => (int) $user['NumUsers'],
        'nomUsers' => $user['nomUsers'],
        'prenomUsers' => $user['prenomUsers'],
        'mailUsers' => $user['mailUsers'],
        'roles' => roles_de((int) $user['NumUsers']),
    ];
    journalise('CONNEXION', $user['mailUsers']);
    return true;
}

function roles_de(int $numUsers): array
{
    $roles = [];
    foreach ([
        'ADMIN' => 'SELECT 1 FROM admin WHERE NumUsers = ?',
        'EMPLOYE' => 'SELECT 1 FROM employe WHERE NumUsers = ?',
        'ADHERENT' => 'SELECT 1 FROM adherent WHERE NumUsers = ?',
    ] as $role => $sql) {
        try {
            $query = db()->prepare($sql);
            $query->execute([$numUsers]);
            if ($query->fetchColumn()) {
                $roles[] = $role;
            }
        } catch (PDOException $exception) {
            error_log('[Apex Fitness] role lookup failed for user ' . $numUsers . ': ' . $exception->getMessage());
        }
    }
    return $roles;
}

function connecte(): bool
{
    return isset($_SESSION['user']);
}

function utilisateur(): ?array
{
    return $_SESSION['user'] ?? null;
}

function a_role(string $role): bool
{
    return connecte() && in_array($role, $_SESSION['user']['roles'], true);
}

function fonction_employe(?int $numUsers = null): ?string
{
    $numUsers ??= (int) utilisateur()['NumUsers'];
    $query = db()->prepare(
        'SELECT f.libellefonc
         FROM employe e JOIN fonction f ON f.Numfonc = e.Numfonc
         WHERE e.NumUsers = ?'
    );
    $query->execute([$numUsers]);
    return $query->fetchColumn() ?: null;
}

function est_technicien_maintenance(): bool
{
    return a_role('EMPLOYE') && fonction_employe() === 'Technicien maintenance';
}

function exige_role(string $role): void
{
    if (!connecte()) {
        flash('info', 'Connectez-vous pour accéder à cette page.');
        redirige('utilisateur/connexion.php?retour=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
    }
    if (!a_role($role) && !a_role('ADMIN')) {
        http_response_code(403);
        flash('erreur', "Votre compte ne dispose pas du rôle $role.");
        redirige('accueil/index.php');
    }
}

function deconnexion(): void
{
    if (connecte()) {
        journalise('DECONNEXION', utilisateur()['mailUsers']);
    }
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'] ?: '/',
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => true,
        'samesite' => $cookie['samesite'] ?? 'Lax',
    ]);
    $_SESSION = [];
    session_destroy();
}

function page_accueil_role(): string
{
    if (a_role('ADMIN')) {
        return 'admin/espace.php';
    }
    if (a_role('EMPLOYE')) {
        return 'employe/espace.php';
    }
    if (a_role('ADHERENT')) {
        return 'adherent/espace.php';
    }
    return 'accueil/index.php';
}
