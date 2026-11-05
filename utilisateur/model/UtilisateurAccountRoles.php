<?php

declare(strict_types=1);

trait UtilisateurAccountRoles
{
    private static function creerRoles(PDO $pdo, int $id, array $roles, int $function): void
    {
        if (in_array('ADMIN', $roles, true)) {
            $pdo->prepare(
                'INSERT INTO admin (NumUsers, matriculeadmin, niveau_droits, date_affectationadmin)
                 VALUES (?, ?, ?, CURDATE())'
            )->execute([$id, 'ADM-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT), 'TOTAL']);
        }
        if (in_array('EMPLOYE', $roles, true)) {
            $function = $function ?: (int) $pdo->query('SELECT MIN(Numfonc) FROM fonction')->fetchColumn();
            $pdo->prepare(
                'INSERT INTO employe (NumUsers, matriculeemp, Date_embaucheemp, Numfonc)
                 VALUES (?, ?, CURDATE(), ?)'
            )->execute([$id, 'EMP-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT), $function]);
        }
        if (in_array('ADHERENT', $roles, true)) {
            $matricule = 'ADH-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
            $pdo->prepare(
                'INSERT INTO adherent (NumUsers, matriculeadh, Date_inscriptionadh, Date_finadh)
                 VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))'
            )->execute([$id, $matricule]);
            self::creerQrAdherent($pdo, $id, $matricule);
        }
    }

    private static function creerQrAdherent(PDO $pdo, int $id, string $matricule): void
    {
        (new GenerateurQrCode())->assurerPourAdherent($pdo, $id, $matricule);
    }
}
