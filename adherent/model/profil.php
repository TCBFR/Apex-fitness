<?php

declare(strict_types=1);

/**
 * Modèle Profil : informations personnelles (table utilisateur), ligne adherent,
 * et validation serveur des champs modifiables.
 */
final class Profil
{
    /** Champs modifiables par l'adhérent (liste blanche : jamais de nom de colonne venant du client). */
    public const EDITABLES = ['nom', 'prenom', 'email', 'telephone'];

    /** champ du formulaire => colonne SQL réelle */
    private const COLONNES = [
        'nom'       => 'nomUsers',
        'prenom'    => 'prenomUsers',
        'email'     => 'mailUsers',
        'telephone' => 'telUsers',
    ];

    /** Mobile/fixe FR : 0X XX XX XX XX, +33 X.., 0033 X.. avec espaces, points ou tirets. */
    private const REGEX_TEL_FR = '/^(?:(?:\+|00)33[\s.-]?(?:\(0\)[\s.-]?)?|0)[1-9](?:[\s.-]?\d{2}){4}$/';
    private const REGEX_NOM = "/^\p{L}[\p{L}\p{M}' .-]*$/u";
    private const MAX = ['nom' => 60, 'prenom' => 60, 'email' => 150];

    /** @var array<string, string> */
    private array $errors = [];

    /* ---------------------------- Base de données ---------------------------- */

    private function fetchOne(string $sql, array $params = []): ?array
    {
        $st = db()->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }

    public function findById(int $numUsers): ?array
    {
        return $this->fetchOne(
            'SELECT NumUsers, nomUsers AS nom, prenomUsers AS prenom, mailUsers AS email, telUsers AS telephone
               FROM utilisateur
              WHERE NumUsers = :id',
            ['id' => $numUsers]
        );
    }

    public function findAdherent(int $numUsers): ?array
    {
        return $this->fetchOne(
            'SELECT NumUsers, matriculeadh, Date_inscriptionadh, Date_finadh
               FROM adherent
              WHERE NumUsers = :id',
            ['id' => $numUsers]
        );
    }

    public function emailExists(string $email, int $excludeNumUsers): bool
    {
        return $this->fetchOne(
            'SELECT 1 FROM utilisateur WHERE mailUsers = :email AND NumUsers <> :id LIMIT 1',
            ['email' => $email, 'id' => $excludeNumUsers]
        ) !== null;
    }

    /** @param array<string, string|null> $fields clés = noms de champs du formulaire */
    public function update(int $numUsers, array $fields): void
    {
        $fields = array_intersect_key($fields, self::COLONNES);
        if ($fields === []) {
            return;
        }
        $set = [];
        $params = ['id' => $numUsers];
        foreach ($fields as $champ => $valeur) {
            $set[] = '`' . self::COLONNES[$champ] . '` = :' . $champ;
            $params[$champ] = $valeur;
        }
        db()->prepare('UPDATE utilisateur SET ' . implode(', ', $set) . ' WHERE NumUsers = :id')->execute($params);
    }

    /* ------------------------------- Validation ------------------------------ */

    /**
     * @param array<string, mixed> $input champs déjà filtrés par liste blanche
     * @return array<string, string|null> valeurs nettoyées prêtes pour la base
     */
    public function validate(array $input): array
    {
        $this->errors = [];
        $clean = [];

        foreach ($input as $field => $raw) {
            if (!is_string($raw)) {
                $this->errors[$field] = 'Valeur invalide.';
                continue;
            }
            $value = trim(preg_replace('/\s+/u', ' ', $raw) ?? '');

            switch ($field) {
                case 'nom':
                case 'prenom':
                    if ($value === '') {
                        $this->errors[$field] = 'Ce champ est obligatoire.';
                    } elseif (mb_strlen($value) > self::MAX[$field]) {
                        $this->errors[$field] = sprintf('%d caractères maximum.', self::MAX[$field]);
                    } elseif (!preg_match(self::REGEX_NOM, $value)) {
                        $this->errors[$field] = 'Lettres, espaces, apostrophes et tirets uniquement.';
                    } else {
                        $clean[$field] = $value;
                    }
                    break;

                case 'email':
                    $value = mb_strtolower($value);
                    if ($value === '') {
                        $this->errors[$field] = "L'email est obligatoire.";
                    } elseif (mb_strlen($value) > self::MAX['email'] || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                        $this->errors[$field] = 'Adresse email invalide.';
                    } else {
                        $clean[$field] = $value;
                    }
                    break;

                case 'telephone':
                    if ($value === '') {
                        $clean[$field] = null;
                    } elseif (!preg_match(self::REGEX_TEL_FR, $value)) {
                        $this->errors[$field] = 'Numéro français invalide (ex : 06 12 34 56 78).';
                    } else {
                        $clean[$field] = self::normaliserTelephone($value);
                    }
                    break;
            }
        }

        return $clean;
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** '+33 6.12.34.56.78' -> '06 12 34 56 78' */
    public static function normaliserTelephone(string $tel): string
    {
        $digits = preg_replace('/\D/', '', $tel) ?? '';
        if (str_starts_with($digits, '0033')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '33') && strlen($digits) >= 11) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 9) {
            $digits = '0' . $digits;
        }
        return implode(' ', str_split($digits, 2));
    }
}