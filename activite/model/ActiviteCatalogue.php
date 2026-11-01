<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/function.php';

/** Catalogue des activités présentées sur l'interface publique. */
final class ActiviteCatalogue
{
    public const TYPES = [
        'cross' => 'Cross Training',
        'pilates' => 'Pilates',
        'abdos' => 'Abdos et gainage',
        'boxe' => 'Boxe',
        'danse' => 'Danse fitness',
        'yoga' => 'Yoga',
        'cardio' => 'Cardio',
        'musculation' => 'Musculation',
    ];

    public static function assurer(PDO $pdo): void
    {
        $existe = (bool) $pdo->query("SHOW TABLES LIKE 'activite_catalogue'")->fetchColumn();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS activite_catalogue (
                Numcatalogue INT AUTO_INCREMENT PRIMARY KEY,
                titre VARCHAR(100) NOT NULL,
                type_activite VARCHAR(20) NOT NULL,
                description TEXT NOT NULL,
                image_path VARCHAR(255) NULL,
                date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_catalogue_type (type_activite)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $colonneImage = $pdo->query("SHOW COLUMNS FROM activite_catalogue LIKE 'image_path'")->fetch(PDO::FETCH_ASSOC);
        if (!$colonneImage) $pdo->exec('ALTER TABLE activite_catalogue ADD image_path VARCHAR(255) NULL AFTER description');

        // À la toute première création, reprend les disciplines déjà affichées depuis le planning.
        if (!$existe) {
            $pdo->exec(
                "INSERT INTO activite_catalogue (titre, type_activite, description)
                 SELECT a.titre, a.type_activite, COALESCE(a.description, '')
                   FROM activite a
                   JOIN (
                     SELECT type_activite, MIN(Numactivite) AS Numactivite
                       FROM activite
                      WHERE type_activite IN ('cross','pilates','abdos','boxe','danse','yoga')
                      GROUP BY type_activite
                   ) premier ON premier.Numactivite = a.Numactivite
                  ORDER BY FIELD(a.type_activite,'cross','pilates','abdos','boxe','danse','yoga')"
            );
            $pdo->exec(
                "INSERT INTO activite_catalogue (titre, type_activite, description)
                 SELECT 'Cross Training', 'cross', 'Le Cross Training enchaîne des exercices qui mobilisent les bras, les épaules, le dos, les abdominaux et les jambes. Ce travail complet développe la force, l’endurance et la coordination, tout en donnant de l’énergie.'
                  WHERE NOT EXISTS (SELECT 1 FROM activite_catalogue WHERE type_activite='cross')"
            );
        }
    }

    public static function toutes(PDO $pdo): array
    {
        return $pdo->query('SELECT Numcatalogue, titre, type_activite, description, image_path FROM activite_catalogue ORDER BY Numcatalogue')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function trouver(PDO $pdo, int $id): ?array
    {
        $query = $pdo->prepare('SELECT Numcatalogue, titre, type_activite, description, image_path FROM activite_catalogue WHERE Numcatalogue=?');
        $query->execute([$id]);
        return $query->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Enregistre une image envoyée par un administrateur et renvoie son chemin public. */
    public static function recevoirImage(array $fichier): ?string
    {
        $erreur = (int) ($fichier['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($erreur === UPLOAD_ERR_NO_FILE) return null;
        if ($erreur === UPLOAD_ERR_INI_SIZE || $erreur === UPLOAD_ERR_FORM_SIZE) {
            $limiteMo = number_format(self::limiteUploadImage() / 1048576, 1, ',', ' ');
            throw new RuntimeException("L’image dépasse la limite de transfert du serveur (environ {$limiteMo} Mo). Elle sera compressée automatiquement si vous la sélectionnez à nouveau.");
        }
        if ($erreur !== UPLOAD_ERR_OK) {
            $message = match ($erreur) {
                UPLOAD_ERR_PARTIAL => 'Le transfert de l’image a été interrompu. Réessayez.',
                UPLOAD_ERR_NO_TMP_DIR => 'Le serveur ne dispose pas de dossier temporaire pour recevoir l’image.',
                UPLOAD_ERR_CANT_WRITE => 'Le serveur n’a pas pu écrire le fichier reçu.',
                UPLOAD_ERR_EXTENSION => 'Le serveur a bloqué le transfert de l’image.',
                default => 'Le transfert de l’image a échoué. Réessayez.',
            };
            throw new RuntimeException($message);
        }
        if ((int) ($fichier['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('L’image ne doit pas dépasser 5 Mo.');
        $temporaire = (string) ($fichier['tmp_name'] ?? '');
        if ($temporaire === '' || !is_uploaded_file($temporaire)) throw new RuntimeException('Le fichier reçu n’est pas une image valide.');

        $types = [
            'image/jpeg' => ['jpg', IMAGETYPE_JPEG],
            'image/png' => ['png', IMAGETYPE_PNG],
            'image/webp' => ['webp', IMAGETYPE_WEBP],
        ];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaire);
        $infos = @getimagesize($temporaire);
        if (!isset($types[$mime]) || !$infos || $infos[2] !== $types[$mime][1]) {
            throw new RuntimeException('Format non accepté. Utilisez une image JPEG, PNG ou WebP.');
        }

        $dossier = __DIR__ . '/../../public/assets/img/activites';
        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            throw new RuntimeException('Le dossier de stockage des images ne peut pas être créé.');
        }
        $nom = bin2hex(random_bytes(16)) . '.' . $types[$mime][0];
        if (!move_uploaded_file($temporaire, $dossier . '/' . $nom)) throw new RuntimeException('Impossible d’enregistrer cette image.');
        return 'assets/img/activites/' . $nom;
    }

    /** Taille cible pour rester sous les limites PHP upload_max_filesize et post_max_size. */
    public static function limiteUploadImage(): int
    {
        $octets = static function (string|false $valeur): int {
            if ($valeur === false || !preg_match('/^\s*(\d+)\s*([KMG]?)\s*$/i', $valeur, $correspondance)) return 0;
            $multiplicateur = match (strtoupper($correspondance[2] ?? '')) { 'G' => 1073741824, 'M' => 1048576, 'K' => 1024, default => 1 };
            return (int) $correspondance[1] * $multiplicateur;
        };
        $limite = 5 * 1048576;
        $uploadMax = $octets(ini_get('upload_max_filesize'));
        $postMax = $octets(ini_get('post_max_size'));
        if ($uploadMax > 0) $limite = min($limite, $uploadMax);
        if ($postMax > 0) $limite = min($limite, max(0, $postMax - 262144));
        return max(131072, $limite - 131072);
    }

    public static function supprimerImage(?string $chemin): void
    {
        if (!$chemin || !preg_match('~\\Aassets/img/activites/[a-f0-9]{32}\\.(?:jpg|png|webp)\\z~', $chemin)) return;
        $fichier = __DIR__ . '/../../public/' . $chemin;
        if (is_file($fichier)) @unlink($fichier);
    }

    public static function valider(array $donnees): array
    {
        $titre = trim((string) ($donnees['titre'] ?? ''));
        $type = (string) ($donnees['type_activite'] ?? '');
        $description = trim((string) ($donnees['description'] ?? ''));
        if ($titre === '' || mb_strlen($titre) > 100) throw new InvalidArgumentException('Le titre est obligatoire (100 caractères maximum).');
        if (!isset(self::TYPES[$type])) throw new InvalidArgumentException('Choisissez un type d’activité valide.');
        if ($description === '' || mb_strlen($description) > 2000) throw new InvalidArgumentException('La description est obligatoire (2 000 caractères maximum).');
        return [$titre, $type, $description];
    }
}
