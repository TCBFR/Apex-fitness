<?php
require_once __DIR__ . '/../../config/function.php';
final class Message {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }

    public function getAllFor(int $userId): array
    {
        $this->assureTable();
        $query = $this->pdo->prepare(
            'SELECT m.*, u.prenomUsers, u.nomUsers
             FROM message_adherent m
             JOIN utilisateur u ON u.NumUsers = m.NumUsers_emetteur
             WHERE m.NumUsers_emetteur = ? OR m.NumUsers_destinataire = ?
             ORDER BY m.date_envoi ASC'
        );
        $query->execute([$userId, $userId]);
        return $query->fetchAll();
    }
    public function setReply(int $userId, int $parentId, string $text): void
    {
        $this->assureTable();
        $parent = $this->getParent($userId, $parentId);
        if (!$parent || trim($text) === '') {
            throw new InvalidArgumentException('Réponse ou message parent invalide.');
        }
        $recipient = (int) $parent['NumUsers_emetteur'] === $userId
            ? (int) $parent['NumUsers_destinataire'] : (int) $parent['NumUsers_emetteur'];
        if (!$this->isAllowedPair($userId, $recipient)) {
            throw new RuntimeException('Cette discussion n’est pas autorisée.');
        }
        $query = $this->pdo->prepare(
            'INSERT INTO message_adherent (NumUsers_emetteur, NumUsers_destinataire, IdMessage_parent, message)
             VALUES (?, ?, ?, ?)'
        );
        $query->execute([$userId, $recipient, $parentId, trim($text)]);
    }
    public function setInitial(int $userId, string $text): void
    {
        $this->assureTable();
        if (trim($text) === '' || !$this->isAdherent($userId)) {
            throw new InvalidArgumentException('Message invalide.');
        }
        $query = $this->pdo->query(
            "SELECT e.NumUsers FROM employe e
             JOIN fonction f ON f.Numfonc = e.Numfonc
             WHERE f.libellefonc <> 'Technicien maintenance'
             ORDER BY e.NumUsers LIMIT 1"
        );
        $recipient = $query->fetchColumn();
        if (!$recipient) throw new RuntimeException('Aucun employé disponible.');
        $insert = $this->pdo->prepare(
            'INSERT INTO message_adherent (NumUsers_emetteur, NumUsers_destinataire, message)
             VALUES (?, ?, ?)'
        );
        $insert->execute([$userId, (int) $recipient, trim($text)]);
    }
    private function getParent(int $userId, int $parentId): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT * FROM message_adherent WHERE IdMessage = ?
             AND (NumUsers_emetteur = ? OR NumUsers_destinataire = ?)'
        );
        $query->execute([$parentId, $userId, $userId]);
        return $query->fetch() ?: null;
    }
    private function isAllowedPair(int $first, int $second): bool
    {
        $query = $this->pdo->prepare(
            "SELECT COUNT(*) FROM adherent a JOIN employe e ON e.NumUsers = ?
             JOIN fonction f ON f.Numfonc = e.Numfonc
             WHERE a.NumUsers = ? AND f.libellefonc <> 'Technicien maintenance'"
        );
        $query->execute([$first, $second]);
        if ((int) $query->fetchColumn() > 0) return true;
        $query->execute([$second, $first]);
        return (int) $query->fetchColumn() > 0;
    }
    private function isAdherent(int $userId): bool
    {
        $query = $this->pdo->prepare('SELECT 1 FROM adherent WHERE NumUsers = ?');
        $query->execute([$userId]);
        return (bool) $query->fetchColumn();
    }
    private function assureTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS message_adherent (
                IdMessage INT AUTO_INCREMENT PRIMARY KEY,
                NumUsers_emetteur INT NOT NULL,
                NumUsers_destinataire INT NOT NULL,
                IdMessage_parent INT NULL,
                message TEXT NOT NULL,
                date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                lu BOOLEAN NOT NULL DEFAULT 0,
                FOREIGN KEY (NumUsers_emetteur) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE,
                FOREIGN KEY (NumUsers_destinataire) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );
        $column = $this->pdo->query("SHOW COLUMNS FROM message_adherent LIKE 'IdMessage_parent'")->fetch();
        if (!$column) $this->pdo->exec('ALTER TABLE message_adherent ADD IdMessage_parent INT NULL AFTER NumUsers_destinataire');
    }
}

final class MessageSuppression
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }

    public function delete(int $userId, int $messageId): void
    {
        $parent = $this->parent($userId, $messageId);
        if (!$parent || !$this->employee($userId)) {
            throw new RuntimeException('Suppression non autorisée.');
        }

        $query = $this->pdo->prepare(
            'SELECT * FROM message_adherent
             WHERE NumUsers_emetteur = ? OR NumUsers_destinataire = ?
             ORDER BY date_envoi'
        );
        $query->execute([$userId, $userId]);
        $messages = $query->fetchAll();
        $byId = array_column($messages, null, 'IdMessage');
        $root = $this->root($parent, $byId);
        $ids = [];

        foreach ($messages as $message) {
            if ((int) $this->root($message, $byId)['IdMessage'] === (int) $root['IdMessage']) {
                $ids[] = $message['IdMessage'];
            }
        }

        if ($ids) {
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $this->pdo->prepare(
                "DELETE FROM message_adherent WHERE IdMessage IN ($marks)"
            )->execute($ids);
        }
    }

    private function parent(int $userId, int $messageId): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT * FROM message_adherent
             WHERE IdMessage = ? AND (NumUsers_emetteur = ? OR NumUsers_destinataire = ?)'
        );
        $query->execute([$messageId, $userId, $userId]);
        return $query->fetch() ?: null;
    }

    private function root(array $message, array $byId): array
    {
        while (!empty($message['IdMessage_parent']) && isset($byId[$message['IdMessage_parent']])) {
            $message = $byId[$message['IdMessage_parent']];
        }
        return $message;
    }

    private function employee(int $userId): bool
    {
        $query = $this->pdo->prepare(
            "SELECT 1 FROM employe e
             JOIN fonction f ON f.Numfonc = e.Numfonc
             WHERE e.NumUsers = ? AND f.libellefonc <> 'Technicien maintenance'"
        );
        $query->execute([$userId]);
        return (bool) $query->fetchColumn();
    }
}
