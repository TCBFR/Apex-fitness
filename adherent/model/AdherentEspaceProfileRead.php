<?php

declare(strict_types=1);

trait AdherentEspaceProfileRead
{
    public function profil(int $id): array
    {
        $columns = $this->pdo->query('SHOW COLUMNS FROM adherent')->fetchAll(PDO::FETCH_COLUMN);
        $address = in_array('adresse_postale', $columns, true) ? 'a.adresse_postale' : "''";
        $postalCode = in_array('code_postal', $columns, true) ? 'a.code_postal' : "''";
        $city = in_array('ville', $columns, true) ? 'a.ville' : "''";
        $communeId = in_array('id_commune', $columns, true) ? 'a.id_commune' : 'NULL';
        $profilePhoto = in_array('photo_profil', $columns, true) ? 'a.photo_profil' : "''";
        $birthDate = in_array('date_naissance', $columns, true) ? 'a.date_naissance' : 'NULL';
        $sex = in_array('sexe', $columns, true) ? 'a.sexe' : "''";
        $communeJoin = in_array('id_commune', $columns, true)
            ? 'LEFT JOIN commune c ON c.id_commune = a.id_commune'
            : '';
        $communeName = in_array('id_commune', $columns, true) ? 'COALESCE(c.nom_commune, ' . $city . ')' : $city;
        $communeCode = in_array('id_commune', $columns, true) ? 'COALESCE(c.code_postal, ' . $postalCode . ')' : $postalCode;
        $q = $this->pdo->prepare(
            'SELECT u.NumUsers, u.nomUsers AS nom, u.prenomUsers AS prenom,
                    u.mailUsers AS email, u.telUsers AS telephone,
                    u.Date_CreationUsers AS cree_le, a.matriculeadh AS matricule,
                    a.Date_inscriptionadh AS inscrit_le, a.Date_finadh AS fin_adhesion,
                    ' . $birthDate . ' AS date_naissance, ' . $sex . ' AS sexe,
                    ' . $address . ' AS adresse_postale, ' . $communeId . ' AS id_commune,
                    ' . $communeName . ' AS nom_commune, ' . $communeCode . ' AS code_postal,
                    ' . $profilePhoto . ' AS photo_profil
             FROM utilisateur u JOIN adherent a ON a.NumUsers = u.NumUsers ' . $communeJoin . '
             WHERE u.NumUsers = ?'
        );
        $q->execute([$id]);
        return $q->fetch() ?: [];
    }
}
