<?php

final class ContactController
{
    public function get(): array
    {
        $user = utilisateur();
        return [
            'nom' => connecte() ? $user['prenomUsers'] . ' ' . $user['nomUsers'] : '',
            'mail' => connecte() ? $user['mailUsers'] : '',
            'sujet' => $_GET['sujet'] ?? '',
            'message' => '',
        ];
    }

    public function set(array $data): array
    {
        $errors = [];
        if (mb_strlen($data['nom']) < 2) $errors['nom'] = 'Indiquez votre nom.';
        if (!filter_var($data['mail'], FILTER_VALIDATE_EMAIL)) $errors['mail'] = 'Adresse e-mail invalide.';
        if (mb_strlen($data['sujet']) < 3) $errors['sujet'] = 'Résumez votre demande.';
        if (mb_strlen($data['message']) < 15) $errors['message'] = '15 caractères minimum.';
        if ($errors) return $errors;
        $query = db()->prepare('INSERT INTO message_contact (nom, mail, sujet, message) VALUES (?, ?, ?, ?)');
        $query->execute([$data['nom'], $data['mail'], $data['sujet'], $data['message']]);
        journalise('MESSAGE_CONTACT', 'Nummessage=' . db()->lastInsertId());
        return [];
    }
}
