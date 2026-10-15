<?php
declare(strict_types=1);

final class CommuneModel
{
    /** Communes dont le nom commence par la saisie. */
    public static function rechercher(string $saisie): array
    {
        $saisie = str_replace(['%', '_'], '', trim($saisie));
        if (mb_strlen($saisie) < 2) {
            return [];
        }

        // « brive la » doit trouver « Brive-la-Gaillarde » : _ remplace n'importe quel caractère.
        $motif = str_replace([' ', '-'], '_', $saisie) . '%';

        $requete = db()->prepare(
            "SELECT id_commune, nom_commune, code_postal
             FROM commune
             WHERE nom_commune LIKE :motif
             ORDER BY nom_commune, code_postal
             LIMIT 10"
        );
        $requete->execute(['motif' => $motif]);

        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function trouver(int $idCommune): ?array
    {
        $requete = db()->prepare(
            'SELECT id_commune, nom_commune, code_postal FROM commune WHERE id_commune = :id'
        );
        $requete->execute(['id' => $idCommune]);

        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}