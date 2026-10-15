<?php

require_once __DIR__ . '/../../config/function.php';
require_once __DIR__ . '/../../qr_code/model/qr_code.php';

final class Paiement
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }
    protected const TABLE = 'paiement';

    public function create(PDO $pdo, array $columns, array $data): void
    {
        $this->saveRecord($pdo, $columns, $data);
    }

    public function update(PDO $pdo, array $columns, array $data, array $key): void
    {
        $this->saveRecord($pdo, $columns, $data, $key);
    }

    public function findById(PDO $pdo, array $columns, array $key): ?array
    {
        $where = $this->keyWhere($columns, $key);
        $query = $pdo->prepare(
            'SELECT * FROM ' . $this->quote(static::TABLE) . ' WHERE ' . $where['sql']
        );
        $query->execute($where['values']);
        return $query->fetch() ?: null;
    }

    public function findAll(PDO $pdo, array $columns): array
    {
        $primary = $this->primaryKey($columns);
        $order = implode(', ', array_map(fn(string $name): string => $this->quote($name), $primary));
        return $pdo->query(
            'SELECT * FROM ' . $this->quote(static::TABLE) . ' ORDER BY ' . $order . ' LIMIT 250'
        )->fetchAll();
    }

    public function delete(PDO $pdo, array $columns, array $key): void
    {
        $where = $this->keyWhere($columns, $key);
        $pdo->prepare(
            'DELETE FROM ' . $this->quote(static::TABLE) . ' WHERE ' . $where['sql']
        )->execute($where['values']);
    }

    private function saveRecord(PDO $pdo, array $columns, array $input, ?array $key = null): void
    {
        $primary = $this->primaryKey($columns);
        $fields = [];
        $values = [];
        foreach ($columns as $column) {
            $name = $column['COLUMN_NAME'];
            $extra = strtolower($column['EXTRA']);
            if (str_contains($extra, 'auto_increment') || str_contains($extra, 'generated')) continue;
            if ($key !== null && in_array($name, $primary, true)) continue;

            $boolean = $column['DATA_TYPE'] === 'tinyint' && $column['COLUMN_TYPE'] === 'tinyint(1)';
            if ($boolean) $value = isset($input[$name]) ? 1 : 0;
            elseif (array_key_exists($name, $input)) $value = is_scalar($input[$name]) ? trim((string) $input[$name]) : '';
            else continue;

            if (stripos($name, 'password') !== false) {
                if ($value === '' && $key !== null) continue;
                if ($value === '') {
                    throw new InvalidArgumentException('Le mot de passe est obligatoire.');
                }
                if (strlen($value) < 8) {
                    throw new InvalidArgumentException('Le mot de passe doit faire 8 caractères minimum.');
                }
                $value = password_hash($value, PASSWORD_DEFAULT);
            }
            if ($value === '' && !$boolean) {
                if ($column['IS_NULLABLE'] === 'YES') $value = null;
                elseif ($column['COLUMN_DEFAULT'] !== null) continue;
                else throw new InvalidArgumentException('Le champ « ' . $name . ' » est obligatoire.');
            }
            if (is_string($value) && in_array($column['DATA_TYPE'], ['datetime', 'timestamp'], true)) {
                $value = str_replace('T', ' ', $value);
                if (strlen($value) === 16) $value .= ':00';
            }
            $fields[] = $name;
            $values[] = $value;
        }

        if ($key === null) {
            $names = array_map(fn(string $name): string => $this->quote($name), $fields);
            $sql = $fields
                ? 'INSERT INTO ' . $this->quote(static::TABLE) . ' (' . implode(', ', $names) .
                    ') VALUES (' . implode(', ', array_fill(0, count($fields), '?')) . ')'
                : 'INSERT INTO ' . $this->quote(static::TABLE) . ' () VALUES ()';
            $pdo->prepare($sql)->execute($values);
            return;
        }

        if (!$fields) throw new InvalidArgumentException('Aucune valeur modifiable n’a été fournie.');
        $where = $this->keyWhere($columns, $key);
        $set = implode(', ', array_map(fn(string $name): string => $this->quote($name) . ' = ?', $fields));
        $pdo->prepare(
            'UPDATE ' . $this->quote(static::TABLE) . ' SET ' . $set . ' WHERE ' . $where['sql']
        )->execute([...$values, ...$where['values']]);
    }

    private function primaryKey(array $columns): array
    {
        $primary = array_values(array_map(
            static fn(array $column): string => $column['COLUMN_NAME'],
            array_filter($columns, static fn(array $column): bool => $column['COLUMN_KEY'] === 'PRI')
        ));
        if (!$primary) throw new RuntimeException('Cette table ne possède pas de clé primaire.');
        return $primary;
    }

    private function keyWhere(array $columns, array $key): array
    {
        $primary = $this->primaryKey($columns);
        if (count($key) !== count($primary)) throw new InvalidArgumentException('Clé de ligne invalide.');
        $parts = [];
        $values = [];
        foreach ($primary as $column) {
            if (!array_key_exists($column, $key) || !is_scalar($key[$column])) {
                throw new InvalidArgumentException('Clé de ligne invalide.');
            }
            $parts[] = $this->quote($column) . ' = ?';
            $values[] = $key[$column];
        }
        return ['sql' => implode(' AND ', $parts), 'values' => $values];
    }

    private function quote(string $identifier): string
    {
        if (!preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/', $identifier)) {
            throw new InvalidArgumentException('Identifiant SQL invalide.');
        }
        return '`' . $identifier . '`';
    }

    public function offres(): array
    {
        return $this->pdo->query(
            'SELECT ab.Numabo, ab.libelle_abo, ab.type_abo,
                    f.prix_mensuel_reduit, b.prix_annuel, p.prix_mensuel_plus
             FROM abonnement ab
             LEFT JOIN freemium f ON f.Numabo = ab.Numabo
             LEFT JOIN basic b ON b.Numabo = ab.Numabo
             LEFT JOIN abonnement_plus p ON p.Numabo = ab.Numabo
             WHERE ab.actif_abo = 1 ORDER BY ab.Numabo'
        )->fetchAll();
    }

    public function payer(int $numUtilisateur, int $numAbonnement, string $moyen, bool $changement = false): float
    {
        if (!in_array($moyen, ['CB', 'Virement', 'Especes', 'Prelevement'], true)) {
            throw new InvalidArgumentException('Moyen de paiement invalide.');
        }

        $this->assureAdherent($numUtilisateur);

        $this->pdo->beginTransaction();
        try {
            $offre = $this->offre($numAbonnement);
            if (!$offre) {
                throw new RuntimeException('Cette formule n’existe pas.');
            }

            $montant = $this->montant($offre);
            $abonnementEnCours = $this->abonnementEnCours($numUtilisateur);
            $dateDebut = date('Y-m-d');
            $dateFin = $this->dateFinPourAbonnement($numAbonnement, $dateDebut);

            if ($abonnementEnCours && $changement) {
                $dateFinAncienne = date('Y-m-d', strtotime('last day of this month'));
                $this->pdo->prepare(
                    "UPDATE souscrire SET statut_souscription = 'Resiliee', date_fin = ?
                     WHERE NumUsers = ? AND statut_souscription = 'En cours'"
                )->execute([$dateFinAncienne, $numUtilisateur]);
                $dateDebut = date('Y-m-d', strtotime('first day of next month'));
                $dateFin = $this->dateFinPourAbonnement($numAbonnement, $dateDebut);
            } elseif ($abonnementEnCours) {
                $this->pdo->prepare(
                    "UPDATE souscrire SET statut_souscription = 'Terminee'
                     WHERE NumUsers = ? AND statut_souscription = 'En cours'"
                )->execute([$numUtilisateur]);
            }

            $this->pdo->prepare(
                "INSERT INTO souscrire (NumUsers, Numabo, date_debut, date_fin, statut_souscription)
                 VALUES (?, ?, ?, ?, 'En cours')"
            )->execute([$numUtilisateur, $numAbonnement, $dateDebut, $dateFin]);

            $this->pdo->prepare(
                'INSERT INTO paiement (montant, moyen_paiement, NumUsers, Numabo) VALUES (?, ?, ?, ?)'
            )->execute([$montant, $moyen, $numUtilisateur, $numAbonnement]);

            $actif = $this->pdo->query(
                "SELECT Numstatut FROM statut WHERE type_statut = 'ADHERENT' AND libelle_statut = 'Actif'"
            )->fetchColumn();
            $this->pdo->prepare(
                'UPDATE adherent SET Numstatut = ?, Date_finadh = ? WHERE NumUsers = ?'
            )->execute([(int) $actif, $dateFin, $numUtilisateur]);

            $this->pdo->commit();
            return $montant;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function assureAdherent(int $numUtilisateur): void
    {
        $exists = $this->pdo->prepare('SELECT 1 FROM adherent WHERE NumUsers = ?');
        $exists->execute([$numUtilisateur]);
        if ($exists->fetch()) {
            $matricule = $this->pdo->prepare('SELECT matriculeadh FROM adherent WHERE NumUsers = ?');
            $matricule->execute([$numUtilisateur]);
            (new GenerateurQrCode())->assurerPourAdherent(
                $this->pdo,
                $numUtilisateur,
                (string) $matricule->fetchColumn()
            );
            return;
        }

        $actif = (int) $this->pdo->query(
            "SELECT Numstatut FROM statut WHERE type_statut = 'ADHERENT' AND libelle_statut = 'Actif'"
        )->fetchColumn();

        $matricule = 'ADH-' . str_pad((string) $numUtilisateur, 4, '0', STR_PAD_LEFT);
        $this->pdo->prepare(
            'INSERT INTO adherent (NumUsers, matriculeadh, Date_inscriptionadh, Date_finadh, Numstatut)
             VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), ?)'
        )->execute([
            $numUtilisateur,
            $matricule,
            $actif,
        ]);
        (new GenerateurQrCode())->assurerPourAdherent($this->pdo, $numUtilisateur, $matricule);
    }

    private function abonnementEnCours(int $numUtilisateur): ?array
    {
        $st = $this->pdo->prepare(
            "SELECT NumUsers, Numabo, date_debut, date_fin, statut_souscription
             FROM souscrire
             WHERE NumUsers = ? AND statut_souscription = 'En cours'
             ORDER BY date_debut DESC LIMIT 1"
        );
        $st->execute([$numUtilisateur]);
        $ligne = $st->fetch();
        return $ligne ?: null;
    }

    private function dateFinPourAbonnement(int $numAbonnement, string $dateDebut): string
    {
        $offre = $this->offre($numAbonnement);
        if (!$offre) {
            throw new RuntimeException('Cette formule n’existe pas.');
        }

        $dt = new DateTimeImmutable($dateDebut);
        if ($offre['type_abo'] === 'BASIC') {
            return $dt->modify('+12 months')->format('Y-m-d');
        }

        return $dt->modify('+1 month')->format('Y-m-d');
    }

    private function offre(int $numAbonnement): ?array
    {
        $requete = $this->pdo->prepare(
            'SELECT ab.Numabo, ab.libelle_abo, ab.type_abo,
                    f.prix_mensuel_reduit, b.prix_annuel, p.prix_mensuel_plus
             FROM abonnement ab
             LEFT JOIN freemium f ON f.Numabo = ab.Numabo
             LEFT JOIN basic b ON b.Numabo = ab.Numabo
             LEFT JOIN abonnement_plus p ON p.Numabo = ab.Numabo
             WHERE ab.Numabo = ? AND ab.actif_abo = 1'
        );
        $requete->execute([$numAbonnement]);
        return $requete->fetch() ?: null;
    }

    private function montant(array $offre): float
    {
        return match ($offre['type_abo']) {
            'FREEMIUM' => (float) $offre['prix_mensuel_reduit'],
            'BASIC' => (float) $offre['prix_annuel'],
            'PLUS' => (float) $offre['prix_mensuel_plus'],
            default => throw new RuntimeException('Tarif de formule introuvable.'),
        };
    }
}
