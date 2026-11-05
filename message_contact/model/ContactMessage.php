<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/function.php';

final class ContactMessage
{
    public function create(array $data): int
    {
        $pdo = db();
        $query = $pdo->prepare(
            'INSERT INTO message_contact (nom, mail, sujet, message) VALUES (?, ?, ?, ?)'
        );
        $query->execute([$data['nom'], $data['mail'], $data['sujet'], $data['message']]);
        return (int) $pdo->lastInsertId();
    }
}
