<?php

declare(strict_types=1);

/**
 * Fonctions de connexion et d'authentification
 */

/** @see tente_connexion() */
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
    return date($withTime ? 'd/m/Y \\a\\ H\\hi' : 'd/m/Y', strtotime($date));
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
        date('H\\hi', $time),
        date('H\\hi', strtotime($end))
    );
}

/**
 * Tente une connexion à l'utilisateur.
 * Vérifie l'email et le mot de passe, crée la session utilisateur.
 *
 * @return true si connexion réussie, false sinon
 *
 * @see utilisateur()
 * @see connecte()
 */
function tente_connexion(string $mail, string $motDePasse, bool $seSouvenir = false): bool
{
    $query = db()->prepare(
        'SELECT NumUsers, nomUsers, prenomUsers, mailUsers, passwordUsers'
    . ' FROM utilisateur WHERE mailUsers = ?'
    );
    $query->execute([$mail]);
    $user = $query->fetch();

    if (!$user || !MotDePasse::verifier($motDePasse, $user['passwordUsers'])) {
        return false;
    }

    if (MotDePasse::doitEtreRehache($user['passwordUsers'])) {
        $hash = MotDePasse::hacher($motDePasse);
        db()->prepare('UPDATE utilisateur SET passwordUsers = ? WHERE NumUsers = ?')
            ->execute([$hash, $user['NumUsers']]);
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