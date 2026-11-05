<?php

declare(strict_types=1);

trait MessageConversationWrite
{
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
             WHERE f.libellefonc = 'Accueil'
             ORDER BY e.NumUsers LIMIT 1"
        );
        $recipient = $query->fetchColumn();
        if (!$recipient) throw new RuntimeException('Aucun employé d’accueil n’est disponible.');
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
             WHERE a.NumUsers = ? AND f.libellefonc = 'Accueil'"
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
}
