<?php

declare(strict_types=1);

trait MessageConversationRead
{
    public function getAllFor(int $userId): array
    {
        $this->assureTable();
        if (!$this->isAccueil($userId)) return [];
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

    private function isAccueil(int $userId): bool
    {
        $query = $this->pdo->prepare(
            "SELECT 1 FROM employe e JOIN fonction f ON f.Numfonc=e.Numfonc
             WHERE e.NumUsers=? AND f.libellefonc='Accueil'"
        );
        $query->execute([$userId]);
        return (bool) $query->fetchColumn();
    }
}
