<?php

declare(strict_types=1);

require_once __DIR__ . '/../model/ContactMessage.php';

final class ContactController
{
    public function __construct(private ?ContactMessage $messages = null)
    {
        $this->messages ??= new ContactMessage();
    }

    public function get(): array
    {
        $user = utilisateur();
        $connected = connecte();
        return [
            'nom' => $connected ? $user['prenomUsers'] . ' ' . $user['nomUsers'] : '',
            'mail' => $connected ? $user['mailUsers'] : '',
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

        $messageId = $this->messages->create($data);
        journalise('MESSAGE_CONTACT', 'Nummessage=' . $messageId);
        return [];
    }
}
