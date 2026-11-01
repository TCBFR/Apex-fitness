<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/function.php';

/** Données et actions propres à l'espace adhérent. */
final class AdherentEspace
{
    public function __construct(private ?PDO $pdo = null)
    {
        $this->pdo ??= db();
    }

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

    public function abonnement(int $id): ?array
    {
        $q = $this->pdo->prepare(
            "SELECT s.date_debut, s.date_fin, s.statut_souscription,
                    a.libelle_abo, a.description_abo, o.type_offre,
                    CASE o.type_offre
                      WHEN 'FREEMIUM' THEN f.prix_mensuel_reduit
                      WHEN 'BASIC' THEN b.prix_annuel
                      WHEN 'PLUS' THEN p.prix_mensuel_plus
                    END AS prix,
                    CASE o.type_offre
                      WHEN 'FREEMIUM' THEN 'mensuel'
                      WHEN 'BASIC' THEN 'annuel'
                      WHEN 'PLUS' THEN 'mensuel'
                    END AS periodicite
             FROM souscrire s JOIN abonnement a ON a.Numabo = s.Numabo
             JOIN offre o ON o.Numoffre = a.Numoffre
             LEFT JOIN freemium f ON f.Numoffre = o.Numoffre
             LEFT JOIN basic b ON b.Numoffre = o.Numoffre
             LEFT JOIN abonnement_plus p ON p.Numoffre = o.Numoffre
             WHERE s.NumUsers = ? AND s.statut_souscription = 'En cours'
               AND s.date_debut <= CURRENT_DATE
               AND (s.date_fin IS NULL OR s.date_fin >= CURRENT_DATE)
             ORDER BY s.date_debut DESC LIMIT 1"
        );
        $q->execute([$id]);
        return $q->fetch() ?: null;
    }

    public function reservations(int $id, bool $avenir = true): array
    {
        $condition = $avenir ? 'AND a.date_heure_debut >= NOW()' : '';
        $q = $this->pdo->prepare(
            "SELECT r.Numres, r.statut_reservation, r.date_heure_reservation,
                    a.Numactivite, a.titre, a.type_activite, a.niveau,
                    a.date_heure_debut, a.date_heure_fin, a.capacite_max,
                    s.nomsalle, s.localisationsalle, coach.prenomUsers AS coach,
                    (SELECT COUNT(*) FROM reservation r2 WHERE r2.Numactivite=a.Numactivite
                       AND r2.statut_reservation <> 'Annulee') AS places_prises
             FROM reservation r JOIN activite a ON a.Numactivite=r.Numactivite
             JOIN salle s ON s.Numsalle=a.Numsalle
             JOIN utilisateur coach ON coach.NumUsers=a.NumUsers
             WHERE r.NumUsers=? AND r.statut_reservation <> 'Annulee' $condition
             ORDER BY a.date_heure_debut"
        );
        $q->execute([$id]);
        return $q->fetchAll();
    }

    public function historiqueReservations(int $id): array
    {
        $q = $this->pdo->prepare(
            "SELECT r.Numres, a.Numactivite, a.titre, a.type_activite, a.date_heure_debut,
                    a.date_heure_fin, s.nomsalle, coach.prenomUsers AS coach
             FROM reservation r JOIN activite a ON a.Numactivite=r.Numactivite
             JOIN salle s ON s.Numsalle=a.Numsalle JOIN utilisateur coach ON coach.NumUsers=a.NumUsers
             WHERE r.NumUsers=? AND r.statut_reservation<>'Annulee' AND a.date_heure_fin < NOW()
             ORDER BY a.date_heure_debut DESC"
        );
        $q->execute([$id]);
        return $q->fetchAll();
    }

    public function paiements(int $id): array
    {
        $q = $this->pdo->prepare(
            'SELECT p.Numpaiement, p.date_paiement, p.montant, p.moyen_paiement,
                    a.libelle_abo FROM paiement p JOIN abonnement a ON a.Numabo=p.Numabo
             WHERE p.NumUsers=? ORDER BY p.date_paiement DESC'
        );
        $q->execute([$id]);
        return $q->fetchAll();
    }

    public function paiement(int $id, int $paymentId): ?array
    {
        // Les installations anciennes peuvent ne pas encore avoir les champs
        // d'adresse ajoutés par la migration d'inscription.
        $adherentColumns = $this->pdo->query('SHOW COLUMNS FROM adherent')->fetchAll(PDO::FETCH_COLUMN);
        $address = in_array('adresse_postale', $adherentColumns, true) ? 'ad.adresse_postale' : "''";
        $city = in_array('ville', $adherentColumns, true) ? 'ad.ville' : "''";
        $postalCode = in_array('code_postal', $adherentColumns, true) ? 'ad.code_postal' : "''";
        $q = $this->pdo->prepare(
            "SELECT p.Numpaiement, p.date_paiement,
                    CASE o.type_offre
                        WHEN 'FREEMIUM' THEN 20.00
                        WHEN 'BASIC' THEN COALESCE(b.prix_annuel, p.montant)
                        WHEN 'PLUS' THEN COALESCE(ap.prix_mensuel_plus, p.montant)
                        ELSE p.montant
                    END AS montant,
                    p.moyen_paiement, o.type_offre,
                    CASE WHEN o.type_offre = 'BASIC' THEN 'annuelle' ELSE 'mensuelle' END AS periodicite,
                    a.libelle_abo, a.description_abo, u.prenomUsers, u.nomUsers, u.mailUsers, u.telUsers AS telephone,
                    {$address} AS adresse_postale, {$city} AS ville, {$postalCode} AS code_postal
             FROM paiement p JOIN abonnement a ON a.Numabo=p.Numabo
             JOIN offre o ON o.Numoffre=a.Numoffre
             LEFT JOIN basic b ON b.Numoffre=o.Numoffre
             LEFT JOIN abonnement_plus ap ON ap.Numoffre=o.Numoffre
             JOIN utilisateur u ON u.NumUsers=p.NumUsers
             JOIN adherent ad ON ad.NumUsers=p.NumUsers
             WHERE p.NumUsers=? AND p.Numpaiement=?"
        );
        $q->execute([$id, $paymentId]);
        return $q->fetch() ?: null;
    }

    public function objectifsDuJour(int $id, string $date): array
    {
        $this->ensureObjectifsTable();
        $q = $this->pdo->prepare('SELECT objectif, atteint FROM objectif_quotidien WHERE NumUsers=? AND date_objectif=? ORDER BY objectif');
        $q->execute([$id, $date]);
        return $q->fetchAll();
    }

    public function historiqueObjectifs(int $id, int $months = 3): array
    {
        $this->ensureObjectifsTable();
        $months = max(1, min(12, $months));
        $q = $this->pdo->prepare(
            "SELECT date_objectif, objectif, atteint FROM objectif_quotidien
             WHERE NumUsers=? AND date_objectif >= DATE_SUB(CURRENT_DATE, INTERVAL {$months} MONTH)
             ORDER BY date_objectif DESC, objectif"
        );
        $q->execute([$id]);
        return $q->fetchAll();
    }

    public function marquerObjectif(int $id, string $date, string $objective, bool $done): void
    {
        $this->ensureObjectifsTable();
        $allowed = ['Séance sportive', 'Boire 2 litres d’eau', 'Dormir 8 heures', '10 000 pas'];
        if (!in_array($objective, $allowed, true)) throw new InvalidArgumentException('Objectif invalide.');
        if ($date !== date('Y-m-d')) throw new InvalidArgumentException('Seuls les objectifs du jour peuvent être modifiés.');
        if ($done) {
            $q = $this->pdo->prepare('INSERT INTO objectif_quotidien (NumUsers,date_objectif,objectif,atteint) VALUES (?,?,?,1) ON DUPLICATE KEY UPDATE atteint=1');
            $q->execute([$id, $date, $objective]);
        } else {
            $q = $this->pdo->prepare('DELETE FROM objectif_quotidien WHERE NumUsers=? AND date_objectif=? AND objectif=?');
            $q->execute([$id, $date, $objective]);
        }
    }

    private function ensureObjectifsTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS objectif_quotidien (
                NumUsers INT NOT NULL,
                date_objectif DATE NOT NULL,
                objectif VARCHAR(80) NOT NULL,
                atteint TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (NumUsers, date_objectif, objectif),
                CONSTRAINT fk_objectif_quotidien_adherent
                    FOREIGN KEY (NumUsers) REFERENCES adherent(NumUsers) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );
    }

    public function dashboard(int $id): array
    {
        $profile = $this->profil($id);
        $subscription = $this->abonnement($id);
        $q = $this->pdo->prepare(
            "SELECT COUNT(*) FROM reservation r JOIN activite a ON a.Numactivite=r.Numactivite
             WHERE r.NumUsers=? AND r.statut_reservation <> 'Annulee' AND a.date_heure_debut >= NOW()"
        );
        $q->execute([$id]);
        $upcoming = (int)$q->fetchColumn();
        $q = $this->pdo->prepare(
            "SELECT COUNT(*) FROM reservation r JOIN activite a ON a.Numactivite=r.Numactivite
             WHERE r.NumUsers=? AND r.statut_reservation <> 'Annulee'
               AND a.date_heure_debut >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
               AND a.date_heure_debut < DATE_ADD(DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'), INTERVAL 1 MONTH)"
        );
        $q->execute([$id]);
        return ['profile'=>$profile, 'subscription'=>$subscription, 'upcoming'=>$upcoming,
            'sessions_month'=>(int)$q->fetchColumn()];
    }

    public function updateProfile(int $id, array $fields): void
    {
        $allowed = ['nom'=>'nomUsers', 'prenom'=>'prenomUsers', 'email'=>'mailUsers', 'telephone'=>'telUsers'];
        $set = [];
        $values = [];
        $adherentSet = [];
        $adherentValues = [];
        foreach ($allowed as $key=>$column) {
            if (!array_key_exists($key, $fields)) continue;
            $value = trim((string)$fields[$key]);
            if (in_array($key, ['nom','prenom'], true) && ($value === '' || mb_strlen($value)>60)) {
                throw new InvalidArgumentException('Le nom et le prénom sont obligatoires (60 caractères maximum).');
            }
            if ($key === 'email' && ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL) || mb_strlen($value)>150)) {
                throw new InvalidArgumentException('Adresse email invalide.');
            }
            if ($key === 'telephone' && $value !== '') {
                if (!preg_match('/^[0-9 ]+$/', $value)) {
                    throw new InvalidArgumentException('Saisissez uniquement les chiffres du numéro de téléphone.');
                }
                $digits = str_replace(' ', '', $value);
                if (strlen($digits) < 6 || strlen($digits) > 10) {
                    throw new InvalidArgumentException('Le numéro doit contenir entre 6 et 10 chiffres.');
                }
                $value = trim(chunk_split($digits, 2, ' '));
            }
            $set[] = "`$column` = ?";
            $values[] = $value === '' ? null : $value;
        }
        $adherentColumns = $this->pdo->query('SHOW COLUMNS FROM adherent')->fetchAll(PDO::FETCH_COLUMN);
        if (array_key_exists('id_commune', $fields)) {
            $communeId = filter_var($fields['id_commune'], FILTER_VALIDATE_INT);
            if (!$communeId || $communeId < 1) {
                throw new InvalidArgumentException('Sélectionnez une commune dans la liste proposée.');
            }
            $communeQuery = $this->pdo->prepare('SELECT nom_commune, code_postal FROM commune WHERE id_commune = ?');
            $communeQuery->execute([$communeId]);
            $commune = $communeQuery->fetch(PDO::FETCH_ASSOC);
            if (!$commune) throw new InvalidArgumentException('La commune sélectionnée est introuvable.');
            if (!in_array('id_commune', $adherentColumns, true)) {
                throw new RuntimeException('La base de données ne permet pas encore d’enregistrer la commune.');
            }
            $adherentSet[] = '`id_commune` = ?';
            $adherentValues[] = $communeId;
            if (in_array('ville', $adherentColumns, true)) {
                $adherentSet[] = '`ville` = ?';
                $adherentValues[] = $commune['nom_commune'];
            }
            if (in_array('code_postal', $adherentColumns, true)) {
                $adherentSet[] = '`code_postal` = ?';
                $adherentValues[] = $commune['code_postal'];
            }
        }
        if (array_key_exists('adresse_postale', $fields)) {
            $address = trim((string) $fields['adresse_postale']);
            if ($address === '' || mb_strlen($address) > 255) {
                throw new InvalidArgumentException('L’adresse est obligatoire (255 caractères maximum).');
            }
            if (!in_array('adresse_postale', $adherentColumns, true)) {
                throw new RuntimeException('La base de données ne permet pas encore d’enregistrer l’adresse.');
            }
            $adherentSet[] = '`adresse_postale` = ?';
            $adherentValues[] = $address;
        }
        if (!$set && !$adherentSet) throw new InvalidArgumentException('Aucune information à modifier.');
        if (array_key_exists('email', $fields)) {
            $q=$this->pdo->prepare('SELECT 1 FROM utilisateur WHERE mailUsers=? AND NumUsers<>?');
            $q->execute([trim((string)$fields['email']), $id]);
            if ($q->fetchColumn()) throw new InvalidArgumentException('Cette adresse email est déjà utilisée.');
        }
        $this->pdo->beginTransaction();
        try {
            if ($set) {
                $values[] = $id;
                $this->pdo->prepare('UPDATE utilisateur SET ' . implode(', ', $set) . ' WHERE NumUsers=?')->execute($values);
            }
            if ($adherentSet) {
                $adherentValues[] = $id;
                $this->pdo->prepare('UPDATE adherent SET ' . implode(', ', $adherentSet) . ' WHERE NumUsers=?')->execute($adherentValues);
            }
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function updateProfilePhoto(int $id, string $photoPath): ?string
    {
        $columns = $this->pdo->query('SHOW COLUMNS FROM adherent')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('photo_profil', $columns, true)) {
            throw new RuntimeException('La base de données ne permet pas encore d’enregistrer la photo de profil.');
        }

        $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare('SELECT photo_profil FROM adherent WHERE NumUsers = ? FOR UPDATE');
            $query->execute([$id]);
            $currentPhoto = $query->fetchColumn();
            if ($currentPhoto === false) throw new RuntimeException('Profil adhérent introuvable.');

            $this->pdo->prepare('UPDATE adherent SET photo_profil = ? WHERE NumUsers = ?')->execute([$photoPath, $id]);
            $this->pdo->commit();
            return $currentPhoto !== '' && $currentPhoto !== null ? (string) $currentPhoto : null;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function messages(int $id): array
    {
        $this->ensureMessagesTable();
        $this->pdo->prepare('UPDATE message_adherent SET lu=1 WHERE NumUsers_destinataire=?')->execute([$id]);
        $q=$this->pdo->prepare(
            "SELECT m.IdMessage, m.NumUsers_emetteur, m.NumUsers_destinataire, m.message, m.date_envoi, m.lu,
                    em.prenomUsers AS emitter_first, em.nomUsers AS emitter_last,
                    dest.prenomUsers AS recipient_first, dest.nomUsers AS recipient_last,
                    emitter_function.libellefonc AS emitter_role,
                    recipient_function.libellefonc AS recipient_role
             FROM message_adherent m
             JOIN utilisateur em ON em.NumUsers=m.NumUsers_emetteur
             JOIN utilisateur dest ON dest.NumUsers=m.NumUsers_destinataire
             LEFT JOIN employe emitter_employee ON emitter_employee.NumUsers=em.NumUsers
             LEFT JOIN fonction emitter_function ON emitter_function.Numfonc=emitter_employee.Numfonc
             LEFT JOIN employe recipient_employee ON recipient_employee.NumUsers=dest.NumUsers
             LEFT JOIN fonction recipient_function ON recipient_function.Numfonc=recipient_employee.Numfonc
             JOIN employe contact_employee ON contact_employee.NumUsers =
                CASE WHEN m.NumUsers_emetteur = ? THEN m.NumUsers_destinataire ELSE m.NumUsers_emetteur END
             JOIN fonction contact_function ON contact_function.Numfonc=contact_employee.Numfonc
             WHERE (m.NumUsers_emetteur=? OR m.NumUsers_destinataire=?)
               AND contact_function.libellefonc='Accueil'
             ORDER BY m.date_envoi"
        );
        $q->execute([$id,$id,$id]);
        return $q->fetchAll();
    }

    public function messagesNonLus(int $id): int
    {
        $this->ensureMessagesTable();
        $q = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM message_adherent m
             JOIN employe e ON e.NumUsers = m.NumUsers_emetteur
             JOIN fonction f ON f.Numfonc = e.Numfonc
             WHERE m.NumUsers_destinataire = ? AND m.lu = 0 AND f.libellefonc = 'Accueil'"
        );
        $q->execute([$id]);
        return (int) $q->fetchColumn();
    }

    public function envoyerMessage(int $id, string $text, ?int $destinataire = null): void
    {
        $this->ensureMessagesTable();
        $text=trim($text);
        if ($text==='' || mb_strlen($text)>4000) throw new InvalidArgumentException('Le message doit contenir entre 1 et 4000 caractères.');
        if ($destinataire === null) {
            $q=$this->pdo->query("SELECT e.NumUsers FROM employe e JOIN fonction f ON f.Numfonc=e.Numfonc WHERE f.libellefonc='Accueil' ORDER BY e.NumUsers LIMIT 1");
            $destinataire=(int)$q->fetchColumn();
        }
        $check=$this->pdo->prepare("SELECT 1 FROM employe e JOIN fonction f ON f.Numfonc=e.Numfonc WHERE e.NumUsers=? AND f.libellefonc='Accueil'");
        $check->execute([$destinataire]);
        $isEmployee=(bool)$check->fetchColumn();
        if (!$destinataire || !$isEmployee) throw new RuntimeException('Aucun employé d’accueil n’est disponible.');
        $this->pdo->prepare('INSERT INTO message_adherent (NumUsers_emetteur,NumUsers_destinataire,message) VALUES (?,?,?)')->execute([$id,$destinataire,$text]);
    }

    private function ensureMessagesTable(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS message_adherent (
            IdMessage INT AUTO_INCREMENT PRIMARY KEY,
            NumUsers_emetteur INT NOT NULL, NumUsers_destinataire INT NOT NULL,
            message TEXT NOT NULL, date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, lu BOOLEAN NOT NULL DEFAULT 0,
            FOREIGN KEY (NumUsers_emetteur) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE,
            FOREIGN KEY (NumUsers_destinataire) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    }
}
