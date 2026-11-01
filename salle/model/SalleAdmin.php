<?php
declare(strict_types=1);

final class SalleAdmin
{
    public const ETATS = [
        'Ouverte' => 'Disponible',
        'Maintenance' => 'En maintenance',
        'Hors service' => 'Hors service',
    ];

    public static function assurer(PDO $pdo): void
    {
        $colonnes = [
            'description_salle' => 'ALTER TABLE salle ADD description_salle TEXT NULL AFTER localisationsalle',
            'image_salle' => 'ALTER TABLE salle ADD image_salle VARCHAR(255) NULL AFTER description_salle',
        ];
        foreach ($colonnes as $colonne => $sql) {
            if (!$pdo->query("SHOW COLUMNS FROM salle LIKE " . $pdo->quote($colonne))->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec($sql);
            }
        }
    }

    public static function valider(array $donnees): array
    {
        $nom = trim((string) ($donnees['nom'] ?? ''));
        $capacite = filter_var($donnees['capacite'] ?? null, FILTER_VALIDATE_INT);
        $localisation = trim((string) ($donnees['localisation'] ?? ''));
        $superficie = trim((string) ($donnees['superficie'] ?? ''));
        $etat = (string) ($donnees['etat'] ?? '');
        $description = trim((string) ($donnees['description'] ?? ''));
        if ($nom === '' || mb_strlen($nom) > 60) throw new InvalidArgumentException('Le nom est obligatoire (60 caractères maximum).');
        if (!$capacite || $capacite < 1 || $capacite > 1000) throw new InvalidArgumentException('Indiquez une capacité comprise entre 1 et 1 000 personnes.');
        if ($localisation !== '' && mb_strlen($localisation) > 80) throw new InvalidArgumentException('La localisation ne doit pas dépasser 80 caractères.');
        if ($superficie !== '' && (!is_numeric($superficie) || (float) $superficie <= 0 || (float) $superficie > 9999.99)) throw new InvalidArgumentException('La superficie doit être un nombre positif.');
        if (!isset(self::ETATS[$etat])) throw new InvalidArgumentException('Choisissez un statut valide.');
        if ($description !== '' && mb_strlen($description) > 2000) throw new InvalidArgumentException('La description ne doit pas dépasser 2 000 caractères.');
        return [$nom, $capacite, $localisation ?: null, $superficie !== '' ? (float) $superficie : null, $etat, $description ?: null];
    }

    public static function recevoirImage(array $fichier): ?string
    {
        $erreur = (int) ($fichier['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($erreur === UPLOAD_ERR_NO_FILE) return null;
        if ($erreur === UPLOAD_ERR_INI_SIZE || $erreur === UPLOAD_ERR_FORM_SIZE) throw new RuntimeException('La photo dépasse la limite de transfert PHP. Sélectionnez-la à nouveau pour appliquer la compression automatique.');
        if ($erreur !== UPLOAD_ERR_OK) throw new RuntimeException('Le transfert de la photo a échoué. Vérifiez le fichier puis réessayez.');
        if ((int) ($fichier['size'] ?? 0) > 5 * 1048576) throw new RuntimeException('La photo ne doit pas dépasser 5 Mo.');
        $temporaire = (string) ($fichier['tmp_name'] ?? '');
        if ($temporaire === '' || !is_uploaded_file($temporaire)) throw new RuntimeException('La photo reçue n’est pas valide.');
        $types = ['image/jpeg' => ['jpg', IMAGETYPE_JPEG], 'image/png' => ['png', IMAGETYPE_PNG], 'image/webp' => ['webp', IMAGETYPE_WEBP]];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaire);
        $infos = @getimagesize($temporaire);
        if (!isset($types[$mime]) || !$infos || $infos[2] !== $types[$mime][1]) throw new RuntimeException('Format non accepté. Utilisez une photo JPEG, PNG ou WebP.');
        $dossier = __DIR__ . '/../../public/assets/img/salles';
        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) throw new RuntimeException('Le dossier des photos de salles ne peut pas être créé.');
        $nom = bin2hex(random_bytes(16)) . '.' . $types[$mime][0];
        if (!move_uploaded_file($temporaire, $dossier . '/' . $nom)) throw new RuntimeException('Impossible d’enregistrer cette photo.');
        return 'assets/img/salles/' . $nom;
    }

    public static function supprimerImage(?string $chemin): void
    {
        if (!$chemin || !preg_match('~\Aassets/img/salles/[a-f0-9]{32}\.(?:jpg|png|webp)\z~', $chemin)) return;
        $fichier = __DIR__ . '/../../public/' . $chemin;
        if (is_file($fichier)) @unlink($fichier);
    }
}
