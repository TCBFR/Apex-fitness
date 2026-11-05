<?php

declare(strict_types=1);

trait UtilisateurAccountQueries
{
    public static function tousLesComptes(): array
    {
        return db()->query(
            'SELECT u.*,
                    (a.NumUsers IS NOT NULL) AS est_admin,
                    (e.NumUsers IS NOT NULL) AS est_employe,
                    (d.NumUsers IS NOT NULL) AS est_adherent,
                    f.libellefonc, e.Numfonc, e.matriculeemp, e.Date_embaucheemp,
                    e.id_commune, c.nom_commune, c.code_postal
             FROM utilisateur u
             LEFT JOIN admin a ON a.NumUsers = u.NumUsers
             LEFT JOIN employe e ON e.NumUsers = u.NumUsers
             LEFT JOIN commune c ON c.id_commune = e.id_commune
             LEFT JOIN adherent d ON d.NumUsers = u.NumUsers
             LEFT JOIN fonction f ON f.Numfonc = e.Numfonc
             ORDER BY u.nomUsers'
        )->fetchAll();
    }

    public static function toutesLesFonctions(): array
    {
        return db()->query('SELECT * FROM fonction ORDER BY libellefonc')->fetchAll();
    }
}
