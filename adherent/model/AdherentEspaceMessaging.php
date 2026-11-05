<?php

declare(strict_types=1);

trait AdherentEspaceMessaging
{
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
        $estEmploye=(bool)$check->fetchColumn();
        if (!$destinataire || !$estEmploye) throw new RuntimeException('Aucun employé d’accueil n’est disponible.');
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
