<?php

declare(strict_types=1);

trait UtilisateurAccountSchema
{
    public static function assurerColonneSexe(PDO $pdo): void
    {
        $colonneSexe = $pdo->query("SHOW COLUMNS FROM adherent LIKE 'sexe'")->fetch(PDO::FETCH_ASSOC);
        if (!$colonneSexe) {
            $pdo->exec('ALTER TABLE adherent ADD COLUMN sexe VARCHAR(30) NULL');
        }
    }

    public static function assurerColonneCommuneEmploye(PDO $pdo): void
    {
        $colonneCommune = $pdo->query("SHOW COLUMNS FROM employe LIKE 'id_commune'")->fetch(PDO::FETCH_ASSOC);
        if (!$colonneCommune) {
            $pdo->exec(
                'ALTER TABLE employe ADD COLUMN id_commune INT NULL,
                 ADD CONSTRAINT fk_employe_commune FOREIGN KEY (id_commune) REFERENCES commune (id_commune)'
            );
        }
    }
}
