<?php

declare(strict_types=1);

/**
 * MODÈLE — QR codes des adhérents (table `qr_code`).
 * Accès aux données + règles métier (signature, statut). Aucune sortie HTTP, aucune image :
 * l'image PNG est dans la vue (view/image.php), les réponses dans le contrôleur.
 *
 * Nécessite la constante APP_SECRET (config/config.php).
 */
final class GenerateurQrCode
{
    private const PREFIXE = 'ATHLETIX';

    /* ------------------------------ Règles métier ------------------------------ */

    /**
     * Valeur signée : ATHLETIX|matricule|NumUsers|jeton|signature (< 120 caractères, colonne code_qr).
     * Le jeton aléatoire permet de regénérer un QR pour le même adhérent (code_qr est UNIQUE).
     */
    public function valeur(string $matricule, int $numUsers, ?string $jeton = null): string
    {
        $jeton ??= bin2hex(random_bytes(4));
        $base = sprintf('%s|%s|%d|%s', self::PREFIXE, $matricule, $numUsers, $jeton);
        return $base . '|' . $this->signature($base);
    }

    /** Vérifie la signature d'une valeur scannée (anti-falsification). */
    public function verifier(string $valeur): bool
    {
        $pos = strrpos($valeur, '|');
        if ($pos === false) {
            return false;
        }
        $base = substr($valeur, 0, $pos);
        return str_starts_with($base, self::PREFIXE . '|')
            && hash_equals($this->signature($base), substr($valeur, $pos + 1));
    }

    /**
     * Statut d'une valeur scannée : FALSIFIE | INCONNU | DESACTIVE | EXPIRE | VALIDE.
     * (L'abonnement est vérifié par le contrôleur, qui croise ce modèle avec Abonnement.)
     *
     * @return array{statut: string, membre: ?array, numUsers: ?int}
     */
    public function controler(PDO $pdo, string $valeur): array
    {
        if (!$this->verifier($valeur)) {
            return ['statut' => 'FALSIFIE', 'membre' => null, 'numUsers' => null];
        }
        $qr = $this->trouverParValeur($pdo, $valeur);
        if ($qr === null) {
            return ['statut' => 'INCONNU', 'membre' => null, 'numUsers' => null];
        }

        $membre = ['nom_complet' => $qr['prenom'] . ' ' . $qr['nom'], 'matricule' => $qr['matriculeadh']];
        $numUsers = (int) $qr['NumUsers'];

        if ((int) $qr['actif_qr'] !== 1) {
            $statut = 'DESACTIVE';
        } elseif (strtotime((string) $qr['date_expiration_qr']) <= time()) {
            $statut = 'EXPIRE';
        } else {
            $statut = 'VALIDE';
        }
        return ['statut' => $statut, 'membre' => $membre, 'numUsers' => $numUsers];
    }

    /* ---------------------------- Base de données ---------------------------- */

    /**
     * Crée le QR d'un adhérent (désactive les précédents) et renvoie sa valeur.
     * À appeler dans la transaction de fin de souscription, après la création de la ligne adherent.
     */
    public function creerPourAdherent(PDO $pdo, int $numUsers, string $matricule, DateTimeInterface $expiration): string
    {
        $valeur = $this->valeur($matricule, $numUsers);
        $pdo->prepare('UPDATE qr_code SET actif_qr = 0 WHERE NumUsers = ?')->execute([$numUsers]);
        $pdo->prepare(
            'INSERT INTO qr_code (code_qr, date_expiration_qr, actif_qr, NumUsers) VALUES (?, ?, 1, ?)'
        )->execute([$valeur, $expiration->format('Y-m-d H:i:s'), $numUsers]);
        return $valeur;
    }

    /** QR actif et non expiré d'un adhérent, ou null. */
    public function actifPourAdherent(PDO $pdo, int $numUsers): ?array
    {
        $st = $pdo->prepare(
            'SELECT Numqr, code_qr, date_generation_qr, date_expiration_qr
               FROM qr_code
              WHERE NumUsers = ? AND actif_qr = 1 AND date_expiration_qr > NOW()
           ORDER BY date_generation_qr DESC, Numqr DESC
              LIMIT 1'
        );
        $st->execute([$numUsers]);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }

    /** Ligne qr_code d'une valeur scannée + identité de l'adhérent (sans filtrer actif/expiration). */
    public function trouverParValeur(PDO $pdo, string $valeur): ?array
    {
        $st = $pdo->prepare(
            'SELECT q.Numqr, q.actif_qr, q.date_expiration_qr, q.NumUsers,
                    u.nomUsers AS nom, u.prenomUsers AS prenom, a.matriculeadh
               FROM qr_code q
               JOIN utilisateur u ON u.NumUsers = q.NumUsers
               JOIN adherent a ON a.NumUsers = q.NumUsers
              WHERE q.code_qr = ?
              LIMIT 1'
        );
        $st->execute([$valeur]);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }

    private function signature(string $base): string
    {
        if (!defined('APP_SECRET') || APP_SECRET === '') {
            throw new RuntimeException('APP_SECRET manquant dans config/config.php.');
        }
        return substr(hash_hmac('sha256', $base, APP_SECRET), 0, 16);
    }
}