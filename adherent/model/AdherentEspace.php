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
        $q = $this->pdo->prepare(
            'SELECT u.NumUsers, u.nomUsers AS nom, u.prenomUsers AS prenom,
                    u.mailUsers AS email, u.telUsers AS telephone,
                    u.Date_CreationUsers AS cree_le, a.matriculeadh AS matricule,
                    a.Date_inscriptionadh AS inscrit_le, a.Date_finadh AS fin_adhesion
             FROM utilisateur u JOIN adherent a ON a.NumUsers = u.NumUsers
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
        foreach ($allowed as $key=>$column) {
            if (!array_key_exists($key, $fields)) continue;
            $value = trim((string)$fields[$key]);
            if (in_array($key, ['nom','prenom'], true) && ($value === '' || mb_strlen($value)>60)) {
                throw new InvalidArgumentException('Le nom et le prénom sont obligatoires (60 caractères maximum).');
            }
            if ($key === 'email' && ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL) || mb_strlen($value)>150)) {
                throw new InvalidArgumentException('Adresse email invalide.');
            }
            if ($key === 'telephone' && $value !== '' && !preg_match('/^[0-9+(). -]{6,20}$/', $value)) {
                throw new InvalidArgumentException('Numéro de téléphone invalide.');
            }
            $set[] = "`$column` = ?";
            $values[] = $value === '' ? null : $value;
        }
        if (!$set) throw new InvalidArgumentException('Aucune information à modifier.');
        if (array_key_exists('email', $fields)) {
            $q=$this->pdo->prepare('SELECT 1 FROM utilisateur WHERE mailUsers=? AND NumUsers<>?');
            $q->execute([trim((string)$fields['email']), $id]);
            if ($q->fetchColumn()) throw new InvalidArgumentException('Cette adresse email est déjà utilisée.');
        }
        $values[]=$id;
        $this->pdo->prepare('UPDATE utilisateur SET '.implode(', ', $set).' WHERE NumUsers=?')->execute($values);
    }

    public function messages(int $id): array
    {
        $this->ensureMessagesTable();
        $this->pdo->prepare('UPDATE message_adherent SET lu=1 WHERE NumUsers_destinataire=?')->execute([$id]);
        $q=$this->pdo->prepare(
            'SELECT m.IdMessage, m.NumUsers_emetteur, m.NumUsers_destinataire, m.message, m.date_envoi, m.lu,
                    em.prenomUsers AS emitter_first, em.nomUsers AS emitter_last,
                    dest.prenomUsers AS recipient_first, dest.nomUsers AS recipient_last
             FROM message_adherent m
             JOIN utilisateur em ON em.NumUsers=m.NumUsers_emetteur
             JOIN utilisateur dest ON dest.NumUsers=m.NumUsers_destinataire
             WHERE m.NumUsers_emetteur=? OR m.NumUsers_destinataire=? ORDER BY m.date_envoi'
        );
        $q->execute([$id,$id]);
        return $q->fetchAll();
    }

    public function envoyerMessage(int $id, string $text, ?int $destinataire = null): void
    {
        $this->ensureMessagesTable();
        $text=trim($text);
        if ($text==='' || mb_strlen($text)>4000) throw new InvalidArgumentException('Le message doit contenir entre 1 et 4000 caractères.');
        if ($destinataire === null) {
            $q=$this->pdo->query("SELECT e.NumUsers FROM employe e JOIN fonction f ON f.Numfonc=e.Numfonc WHERE f.libellefonc <> 'Technicien maintenance' ORDER BY e.NumUsers LIMIT 1");
            $destinataire=(int)$q->fetchColumn();
        }
        $check=$this->pdo->prepare("SELECT 1 FROM employe e JOIN fonction f ON f.Numfonc=e.Numfonc WHERE e.NumUsers=? AND f.libellefonc<>'Technicien maintenance'");
        $check->execute([$destinataire]);
        $isEmployee=(bool)$check->fetchColumn();
        if (!$destinataire) throw new RuntimeException('Aucun membre de l’équipe n’est disponible.');
        if (!$isEmployee) {
            $pair=$this->pdo->prepare('SELECT 1 FROM message_adherent WHERE (NumUsers_emetteur=? AND NumUsers_destinataire=?) OR (NumUsers_emetteur=? AND NumUsers_destinataire=?) LIMIT 1');
            $pair->execute([$id,$destinataire,$destinataire,$id]);
            if (!$pair->fetchColumn()) throw new RuntimeException('Cette conversation n’est pas autorisée.');
        }
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
