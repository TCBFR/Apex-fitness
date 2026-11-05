<?php

declare(strict_types=1);

/**
 * Détermine la page d'accueil en fonction du rôle de l'utilisateur
 *
 * @return string Chemin vers la page d'accueil appropriée
 *
 * @see a_role()
 */
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
/**
 * Retourne les rôles d'un utilisateur (ADMIN, EMPLOYE, ADHERENT).
 *
 * @see a_role()
 */
function roles_de(int $numUsers): array
{
    $tables = [
        'ADMIN'    => 'admin',
        'EMPLOYE'  => 'employe',
        'ADHERENT' => 'adherent',
    ];
    $roles = [];
    foreach ($tables as $role => $table) {
        $query = db()->prepare("SELECT 1 FROM $table WHERE NumUsers = ? LIMIT 1");
        $query->execute([$numUsers]);
        if ($query->fetchColumn()) {
            $roles[] = $role;
        }
    }
    return $roles;
}