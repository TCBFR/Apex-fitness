<?php
/**
 * Apex Fitness - configuration, connexion PDO, helpers.
 * Inclus par toutes les pages.
 */

declare(strict_types=1);

// --- Parametres de connexion (a adapter a votre environnement) -------
const DB_HOST = '127.0.0.1';
const DB_NAME = 'apex_fitness';
const DB_USER = 'root';
const DB_PASS = 'root';
const DB_PORT = 8889;

const APP_NOM  = 'apex_fitness';
const APP_BASE = '';   // sous-dossier eventuel, ex. '/apex-fitness'

// --- Affichage des erreurs : a passer a 0 en production --------------
ini_set('display_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set('Europe/Paris');

// --- Session ---------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// --- Connexion PDO ---------------------------------------------------
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Connexion a la base impossible. Verifiez config/config.php. Detail : ' . $e->getMessage());
        }
    }
    return $pdo;
}

/** Echappement systematique de toute sortie HTML. */
function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $chemin): string
{
    return APP_BASE . '/' . ltrim($chemin, '/');
}

function redirige(string $chemin): never
{
    header('Location: ' . url($chemin));
    exit;
}

// --- Jeton CSRF ------------------------------------------------------
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
    $envoye = $_POST['csrf'] ?? '';
    if (!is_string($envoye) || !hash_equals($_SESSION['csrf'] ?? '', $envoye)) {
        http_response_code(419);
        exit('Session expiree. Rechargez la page et recommencez.');
    }
}

// --- Messages flash --------------------------------------------------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashs(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// --- Journal d'activite ----------------------------------------------
function journalise(string $action, ?string $cible = null): void
{
    $sql = 'INSERT INTO journal (NumUsers, action, cible, ip) VALUES (?, ?, ?, ?)';
    db()->prepare($sql)->execute([
        $_SESSION['user']['NumUsers'] ?? null,
        $action,
        $cible,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

// --- Formatage -------------------------------------------------------
function euros(float|string|null $n): string
{
    return number_format((float) $n, 2, ',', ' ') . ' &euro;';
}

function dateFr(?string $d, bool $avecHeure = false): string
{
    if (!$d) return '&mdash;';
    $t = strtotime($d);
    return date($avecHeure ? 'd/m/Y \a\ H\hi' : 'd/m/Y', $t);
}

function jourHeure(string $debut, string $fin): string
{
    $jours = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
    $mois  = ['', 'jan.', 'fev.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'aout', 'sept.', 'oct.', 'nov.', 'dec.'];
    $t = strtotime($debut);
    return sprintf(
        '%s %d %s &middot; %s &ndash; %s',
        $jours[(int) date('w', $t)],
        (int) date('j', $t),
        $mois[(int) date('n', $t)],
        date('H\hi', $t),
        date('H\hi', strtotime($fin))
    );
}
