<?php
/**
 * Authentification et controle des roles.
 *
 * L'heritage du MCD est total mais NON exclusif : un meme NumUsers peut
 * exister a la fois dans employe et dans adherent. Les roles sont donc
 * stockes dans un tableau, jamais dans une colonne unique.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

/** Charge l'utilisateur et tous ses roles a partir de son e-mail. */
function tente_connexion(string $mail, string $motDePasse): bool
{
    $sql = 'SELECT NumUsers, nomUsers, prenomUsers, mailUsers, passwordUsers
            FROM utilisateur WHERE mailUsers = ?';
    $st = db()->prepare($sql);
    $st->execute([$mail]);
    $u = $st->fetch();

    if (!$u || !password_verify($motDePasse, $u['passwordUsers'])) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['user'] = [
        'NumUsers'    => (int) $u['NumUsers'],
        'nomUsers'    => $u['nomUsers'],
        'prenomUsers' => $u['prenomUsers'],
        'mailUsers'   => $u['mailUsers'],
        'roles'       => roles_de((int) $u['NumUsers']),
    ];

    journalise('CONNEXION', $u['mailUsers']);
    return true;
}

/** Retourne les roles reellement portes par l'utilisateur. */
function roles_de(int $numUsers): array
{
    $roles = [];
    foreach ([
        'ADMIN'    => 'SELECT 1 FROM administrateur WHERE NumUsers = ?',
        'EMPLOYE'  => 'SELECT 1 FROM employe        WHERE NumUsers = ?',
        'ADHERENT' => 'SELECT 1 FROM adherent       WHERE NumUsers = ?',
    ] as $role => $sql) {
        $st = db()->prepare($sql);
        $st->execute([$numUsers]);
        if ($st->fetchColumn()) {
            $roles[] = $role;
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
    $requete = db()->prepare(
        'SELECT f.libellefonc FROM employe e JOIN fonction f ON f.Numfonc = e.Numfonc WHERE e.NumUsers = ?'
    );
    $requete->execute([$numUsers]);
    return $requete->fetchColumn() ?: null;
}

function est_technicien_maintenance(): bool
{
    return a_role('EMPLOYE') && fonction_employe() === 'Technicien maintenance';
}

/** Bloque l'acces a la page si le role n'est pas porte. */
function exige_role(string $role): void
{
    if (!connecte()) {
        flash('info', 'Connectez-vous pour acceder a cette page.');
        redirige('login.php?retour=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
    }
    if (!a_role($role) && !a_role('ADMIN')) {
        http_response_code(403);
        flash('erreur', "Votre compte ne dispose pas du role $role.");
        redirige('index.php');
    }
}

function deconnexion(): void
{
    if (connecte()) {
        journalise('DECONNEXION', utilisateur()['mailUsers']);
    }
    $_SESSION = [];
    session_destroy();
}

/** Page d'atterrissage apres connexion, selon le role le plus large. */
function page_accueil_role(): string
{
    if (a_role('ADMIN'))    return 'admin.php';
    if (a_role('EMPLOYE'))  return 'employe.php';
    if (a_role('ADHERENT')) return 'adherent.php';
    return 'index.php';
}
