<?php
require_once __DIR__ . '/Model.php';

final class MessageSuppression extends Model
{
    public function delete(int $userId, int $messageId): void
    {
        $parent = $this->parent($userId, $messageId);
        if (!$parent || !$this->employee($userId)) throw new RuntimeException('Suppression non autorisée.');
        $query = $this->pdo->prepare('SELECT * FROM message_adherent WHERE NumUsers_emetteur = ? OR NumUsers_destinataire = ? ORDER BY date_envoi');
        $query->execute([$userId, $userId]);
        $messages = $query->fetchAll();
        $byId = array_column($messages, null, 'IdMessage');
        $root = $this->root($parent, $byId);
        $ids = [];
        foreach ($messages as $message) {
            if ((int) $this->root($message, $byId)['IdMessage'] === (int) $root['IdMessage']) $ids[] = $message['IdMessage'];
        }
        if ($ids) {
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $this->pdo->prepare("DELETE FROM message_adherent WHERE IdMessage IN ($marks)")->execute($ids);
        }
    }

    private function parent(int $userId, int $messageId): ?array
    {
        $query = $this->pdo->prepare('SELECT * FROM message_adherent WHERE IdMessage = ? AND (NumUsers_emetteur = ? OR NumUsers_destinataire = ?)');
        $query->execute([$messageId, $userId, $userId]);
        return $query->fetch() ?: null;
    }

    private function root(array $message, array $byId): array
    {
        while (!empty($message['IdMessage_parent']) && isset($byId[$message['IdMessage_parent']])) $message = $byId[$message['IdMessage_parent']];
        return $message;
    }

    private function employee(int $userId): bool
    {
        $query = $this->pdo->prepare("SELECT 1 FROM employe e JOIN fonction f ON f.Numfonc = e.Numfonc WHERE e.NumUsers = ? AND f.libellefonc <> 'Technicien maintenance'");
        $query->execute([$userId]);
        return (bool) $query->fetchColumn();
    }
}
